// @ts-check

// Keeps widgets supplied with up-to-date data, without them having to care about where that data
// comes from. Widgets register their interest in a channel (see components/LiveDataChannels.php) and
// get called back whenever new data is available.
//
// If the Antragsgrün Live server is configured and reachable, the data is pushed via websockets.
// Otherwise - or while the connection is down - the channel is polled. Polling only happens as long
// as at least one widget is registered and the browser tab is visible.
//
// The configuration (which channels exist, how to poll them, how to subscribe to them) comes from the
// "live-data-config" meta tag, which is rendered by views/layouts/main.php for all channels the view
// registered using $layout->addLiveDataChannel().

import { authorizedFetch, getToken, invalidateToken } from "/js/modules/shared/ApiClient.js";

/**
 * @typedef {object} ChannelConfig
 * @property {string} role
 * @property {string} channel
 * @property {string} poll_url
 * @property {'jwt'|'jwt-optional'} auth
 * @property {number} interval
 * @property {boolean} interval_configured
 * @property {string|null} key_placeholder
 */

/**
 * @typedef {object} Registration
 * @property {string|number|null} key
 * @property {number|null} intervalMs
 * @property {function(any): void} onData
 * @property {function(Error): void|null} onError
 */

const MAX_BACKOFF_MS = 30000;

/**
 * How often a rejected request is retried with a freshly fetched token before the channel is given up
 * on. A token can be rejected even though this browser still considered it valid, and renewing it fixes
 * that - but if renewing it does not, the data is simply not accessible to this user.
 */
const MAX_AUTH_RETRIES = 3;

/**
 * Widgets registering later than this after the page was loaded cannot rely on the data the backend
 * rendered them with anymore - it would already be stale. This is the regular case for the fullscreen
 * projector and for widgets shown by a widget that itself is kept up to date.
 */
const MAX_AGE_OF_INITIAL_DATA_MS = 5000;

/** When this module was loaded, which is as good an approximation of the page load as we need */
const INITIALIZED_AT = (new Date()).getTime();

class Channel {
    /** @type {Registration[]} */ registrations = [];
    /** @type {boolean} */ liveConnected = false;
    /** @type {number|null} */ timerId = null;
    /** @type {boolean} */ requestRunning = false;
    /** @type {boolean} */ refetchRequested = false;
    /** @type {number} */ consecutiveErrors = 0;
    /** @type {number} */ consecutiveAuthErrors = 0;
    /** Set when the data is not accessible to this user at all; retrying would not help */
    /** @type {boolean} */ givenUp = false;

    /**
     * @param {ChannelConfig} config
     */
    constructor(config) {
        this.config = config;
    }

    /**
     * @param {Registration} registration
     */
    addRegistration(registration) {
        this.registrations.push(registration);
        this.reschedulePolling();
    }

    /**
     * @param {Registration} registration
     */
    removeRegistration(registration) {
        this.registrations = this.registrations.filter(existing => existing !== registration);
        this.reschedulePolling();
    }

    /**
     * The keys (currently: speaking list IDs) of all registered widgets, in the order they registered.
     *
     * @returns {(string|number)[]}
     */
    getKeys() {
        const keys = [];
        this.registrations.forEach(registration => {
            if (registration.key !== null && registration.key !== undefined && keys.indexOf(registration.key) === -1) {
                keys.push(registration.key);
            }
        });
        return keys;
    }

    /**
     * The most frequent update rate any of the registered widgets asked for. If an interval was set
     * for this channel in the configuration, that one is binding and widgets cannot ask for more.
     *
     * @returns {number}
     */
    getIntervalMs() {
        if (this.config.interval_configured) {
            return this.config.interval;
        }

        let interval = this.config.interval;
        this.registrations.forEach(registration => {
            if (registration.intervalMs !== null && registration.intervalMs < interval) {
                interval = registration.intervalMs;
            }
        });
        return interval;
    }

    /**
     * @returns {string|null} null if the channel is keyed, but no widget is interested in any key yet
     */
    getPollUrl() {
        if (this.config.key_placeholder === null) {
            return this.config.poll_url;
        }

        const keys = this.getKeys();
        if (keys.length === 0) {
            return null;
        }

        return this.config.poll_url.replace(this.config.key_placeholder, keys.join(','));
    }

    /**
     * Hands data - either polled or received as live event - to the widgets it is meant for.
     * Keyed channels deliver a list of objects when polled, but a single object as a live event.
     *
     * @param {any} data
     */
    publishData(data) {
        if (this.config.key_placeholder === null) {
            this.registrations.forEach(registration => registration.onData(data));
            return;
        }

        const items = Array.isArray(data) ? data : [data];
        items.forEach(item => {
            this.registrations.forEach(registration => {
                if (registration.key === item.id) {
                    registration.onData(item);
                }
            });
        });
    }

    /**
     * @returns {Promise<Response>}
     */
    performRequest(url) {
        if (this.config.auth === 'jwt-optional' && !hasJwt()) {
            return fetch(url, { headers: { 'Accept': 'application/json' } });
        }
        return authorizedFetch(url);
    }

    /**
     * Loads the current data immediately, regardless of the polling schedule and of the live connection.
     * Necessary as live events only carry changes, never the current state.
     */
    fetchNow() {
        if (this.givenUp) {
            return;
        }
        if (this.requestRunning) {
            // Don't stack requests; the data being loaded right now might not contain what was just asked for
            this.refetchRequested = true;
            return;
        }
        const url = this.getPollUrl();
        if (url === null) {
            return;
        }

        this.requestRunning = true;
        // Wrapped in a promise, so that a failure to even start the request (like a missing JWT)
        // is handled by the catch below instead of escaping this method
        return Promise.resolve()
            .then(() => this.performRequest(url))
            .then(response => {
                if (response.status === 401 || response.status === 403) {
                    this.consecutiveAuthErrors++;
                    if (this.canRetryAuth()) {
                        // The token this browser holds was rejected although it still considered it
                        // valid; a freshly fetched one may well be accepted, so drop it and retry.
                        invalidateToken();
                    } else {
                        // Polling this channel will not start working by trying again - e.g. anonymous
                        // users on installations where the public API is disabled, or a user without the
                        // privilege the channel needs.
                        this.givenUp = true;
                    }
                    throw new Error('Not permitted to access ' + this.config.role + '/' + this.config.channel);
                }
                if (!response.ok) {
                    throw new Error('HTTP status ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                this.consecutiveErrors = 0;
                this.consecutiveAuthErrors = 0;
                this.publishData(data);
                return data;
            })
            .catch(err => {
                // Failing requests are retried with a growing delay: a backend that is temporarily
                // unavailable (a restart, a flaky connection) must not stop the widgets for good.
                this.consecutiveErrors++;
                console.error('Could not load ' + this.config.role + '/' + this.config.channel + ' data from backend', err);
                this.registrations.forEach(registration => {
                    if (registration.onError) {
                        registration.onError(err);
                    }
                });
            })
            .finally(() => {
                this.requestRunning = false;
                if (this.refetchRequested) {
                    this.refetchRequested = false;
                    this.fetchNow();
                } else {
                    this.reschedulePolling();
                }
            });
    }

    /**
     * Whether a rejected request is worth repeating. Only makes sense if this page actually holds a
     * token: there is one to renew then, and the renewal goes through the session-authenticated token
     * endpoint. Without a token there is nothing to retry with.
     */
    canRetryAuth() {
        if (!hasJwt()) {
            return false;
        }
        return this.consecutiveAuthErrors <= MAX_AUTH_RETRIES;
    }

    shouldPoll() {
        return !this.givenUp && !this.liveConnected && !document.hidden &&
            this.registrations.length > 0 && this.getPollUrl() !== null;
    }

    reschedulePolling() {
        if (this.timerId !== null) {
            window.clearTimeout(this.timerId);
            this.timerId = null;
        }
        if (!this.shouldPoll()) {
            return;
        }

        // Back off when the backend is failing, instead of hammering it at the regular rate
        const delay = Math.min(this.getIntervalMs() * Math.pow(2, this.consecutiveErrors), MAX_BACKOFF_MS);

        this.timerId = window.setTimeout(() => {
            this.timerId = null;
            this.fetchNow();
        }, delay);
    }

    /**
     * @param {boolean} connected
     */
    setLiveConnected(connected) {
        this.liveConnected = connected;
        this.reschedulePolling();
    }
}

/** @type {{channels: ChannelConfig[], live: object|null}|null} */
let config = null;

/** @type {Object<string, Channel>} */
const channels = {};

/** @type {boolean} */
let stompStarted = false;

/** @type {boolean} */
let hasBeenConnected = false;

function hasJwt() {
    return document.head.querySelector('meta[name=user-jwt-config]') !== null;
}

function getConfig() {
    if (config === null) {
        const meta = document.head.querySelector('meta[name=live-data-config]');
        if (!meta) {
            throw new Error('No live-data-config meta tag found - the view needs to call $layout->addLiveDataChannel()');
        }
        config = JSON.parse(meta.getAttribute('content') || '');
    }
    return config;
}

/**
 * @param {string} role
 * @param {string} channel
 * @returns {Channel}
 */
function getChannel(role, channel) {
    const key = role + '/' + channel;
    if (!channels[key]) {
        const channelConfig = getConfig().channels.find(
            candidate => candidate.role === role && candidate.channel === channel
        );
        if (!channelConfig) {
            throw new Error('The channel ' + key + ' was not registered by this view');
        }
        channels[key] = new Channel(channelConfig);
    }
    return channels[key];
}

function connectToLiveServer() {
    if (stompStarted) {
        return;
    }
    stompStarted = true;

    const liveConfig = getConfig().live;
    if (!liveConfig) {
        return; // No Live server configured for this installation - polling it is
    }
    if (window['StompJs'] === undefined || !hasJwt()) {
        console.warn('The Live server is configured, but the STOMP client or the JWT is missing');
        return;
    }

    const setConnectedForAllChannels = (connected) => {
        getConfig().channels.forEach(channelConfig => {
            getChannel(channelConfig.role, channelConfig.channel).setLiveConnected(connected);
        });
    };

    const stompClient = new window['StompJs'].Client({
        brokerURL: liveConfig['uri'],
        debug: () => {},
        reconnectDelay: 5000,
        heartbeatIncoming: 4000,
        heartbeatOutgoing: 4000,
        connectHeaders: {
            installation: liveConfig['installation'],
        },
    });

    stompClient.beforeConnect = () => {
        return getToken().then(token => {
            stompClient.connectHeaders.jwt = token;
            return token;
        });
    };

    stompClient.onConnect = () => {
        console.info('Connected to Antragsgrün Live Server');
        getConfig().channels.forEach(channelConfig => {
            const topicUrl = '/' + channelConfig.role + '/' + liveConfig['installation'] + '/' + liveConfig['subdomain'] +
                '/' + liveConfig['consultation'] + '/' + encodeURIComponent(liveConfig['user_id']) + '/' + channelConfig.channel;
            stompClient.subscribe(topicUrl, message => {
                getChannel(channelConfig.role, channelConfig.channel).publishData(JSON.parse(message.body));
            });
        });
        setConnectedForAllChannels(true);

        if (hasBeenConnected) {
            // Live events only carry changes: everything that happened while the connection was down
            // would be missing otherwise. On the first connect, the widgets already have current data.
            getConfig().channels.forEach(channelConfig => {
                const channel = getChannel(channelConfig.role, channelConfig.channel);
                if (channel.registrations.length > 0) {
                    channel.fetchNow();
                }
            });
        }
        hasBeenConnected = true;
    };

    stompClient.onWebSocketError = (error) => {
        console.error('Error with websocket', error);
        setConnectedForAllChannels(false);
    };

    stompClient.onWebSocketClose = () => {
        setConnectedForAllChannels(false);
    };

    stompClient.onStompError = (frame) => {
        console.error('Broker reported error: ' + frame.headers['message']);
        console.error('Additional details: ' + frame.body);
        setConnectedForAllChannels(false);
    };

    stompClient.activate();
}

document.addEventListener('visibilitychange', () => {
    Object.values(channels).forEach(channel => {
        if (document.hidden) {
            channel.reschedulePolling(); // Stops the polling while the tab is in the background
        } else if (!channel.liveConnected && channel.registrations.length > 0) {
            channel.fetchNow(); // Don't show data that got stale while the tab was in the background
        }
    });
});

/**
 * Registers the interest of a widget in the data of a channel. The returned handle is used to adjust
 * the registration and needs to be unregistered once the widget goes away.
 *
 * `initialFetch` only needs to be set by widgets that have no data to start with at all. Widgets
 * registering later than a few seconds after the page was loaded always get the current data loaded,
 * as the state they were initialized with would be stale by then.
 *
 * @param {string} role
 * @param {string} channel
 * @param {{onData: function(any): void, onError?: function(Error): void, key?: string|number|null, intervalMs?: number|null, initialFetch?: boolean}} options
 * @returns {{setKey: function(string|number|null): void, setIntervalMs: function(number|null): void, refreshNow: function(): void, unregister: function(): void}}
 */
export function registerListener(role, channel, options) {
    const channelObj = getChannel(role, channel);

    /** @type {Registration} */
    const registration = {
        key: options.key !== undefined ? options.key : null,
        intervalMs: options.intervalMs !== undefined ? options.intervalMs : null,
        onData: options.onData,
        onError: options.onError !== undefined ? options.onError : null,
    };
    channelObj.addRegistration(registration);

    connectToLiveServer();

    const initialDataIsStale = ((new Date()).getTime() - INITIALIZED_AT) > MAX_AGE_OF_INITIAL_DATA_MS;
    if (options.initialFetch || initialDataIsStale) {
        channelObj.fetchNow();
    }

    return {
        setKey: (key) => {
            if (registration.key === key) {
                return;
            }
            registration.key = key;
            if (key !== null) {
                channelObj.fetchNow();
            }
            channelObj.reschedulePolling();
        },
        setIntervalMs: (intervalMs) => {
            registration.intervalMs = intervalMs;
            channelObj.reschedulePolling();
        },
        refreshNow: () => {
            channelObj.fetchNow();
        },
        unregister: () => {
            channelObj.removeRegistration(registration);
        },
    };
}
