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
//
// Three kinds of channel exist:
// - plain: one object describes the whole state (the debate).
// - keyed: the state of specific objects, addressed by ID; widgets say which one they show, and a
//   poll asks for all of them at once (the speaking lists).
// - collection: a list whose members come and go, and which a widget shows a filtered view of. A poll
//   answers with the whole list, a live event with a single member, merged into it by ID (the
//   votings). Widgets always receive the whole list and filter it themselves, since which members
//   are interesting is a question only the widget can answer.

import { apiFetch, authorizedFetch, getToken, invalidateToken } from "/js/modules/shared/ApiClient.js";

/**
 * @typedef {object} ChannelConfig
 * @property {string} role
 * @property {string} channel
 * @property {string} poll_url
 * @property {'jwt'|'jwt-optional'} auth
 * @property {number} interval
 * @property {boolean} interval_configured
 * @property {string|null} key_placeholder
 * @property {boolean} collection
 * @property {boolean} poll_while_live
 */

/**
 * @typedef {object} Registration
 * @property {string|number|null} key
 * @property {number|null} intervalMs
 * @property {function(any): void} onData
 * @property {function(Error): void|null} onError
 */

/**
 * Merges the fields a partial live event carries into the member a client already holds.
 *
 * Plain fields are replaced. A list of objects that carry an `id` is merged member by member
 * instead, because such a list can describe the same things twice with a different amount of
 * detail: a voting's item groups are named in every tally, for the counting, but the single votes
 * within them are left out of tallies entirely - the one part of the payload that grows with the
 * number of people voting. Replacing the list would drop what the event never meant to change; a
 * key the event does carry still wins, null included.
 *
 * The incoming list decides which members exist - an item group that is gone is gone.
 *
 * @param {Record<string, any>} member
 * @param {Record<string, any>} fields
 * @returns {Record<string, any>}
 */
function mergeMember(member, fields) {
    const merged = Object.assign({}, member);
    Object.keys(fields).forEach(key => {
        merged[key] = mergeKeyedList(member[key], fields[key]);
    });

    return merged;
}

/**
 * @param {any} known
 * @param {any} incoming
 * @returns {any}
 */
function mergeKeyedList(known, incoming) {
    if (!isKeyedList(known) || !isKeyedList(incoming)) {
        return incoming;
    }

    return incoming.map(item => {
        const previous = known.find(candidate => candidate.id === item.id);
        return previous ? Object.assign({}, previous, item) : item;
    });
}

/**
 * @param {any} value
 * @returns {boolean}
 */
function isKeyedList(value) {
    return Array.isArray(value) && value.every(
        item => item !== null && typeof item === 'object' && !Array.isArray(item) && item.id !== undefined
    );
}

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
    /**
     * The server time of the most recent payload the widgets were given, per key (channels without a
     * key use the empty string). Every payload carries the time it was serialized at, so one that
     * arrives late can be recognized as describing a state that has already been superseded;
     * publishing it would set the widgets back until the next update arrives. That happens whenever a
     * request is overtaken by a change - no matter whether this browser or somebody else made it -
     * and is the regular case for a poll racing a live event.
     * @type {Object<string, number>}
     */
    lastPublishedAt = {};
    /**
     * The members of a collection channel, in the order the backend listed them, merged across polls
     * and live events. Empty for the other kinds of channel.
     * @type {any[]}
     */
    collection = [];
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
     * Whether a payload describes a state that is newer than the one the widgets were last given for
     * this key, remembering it as the newest one if so. Payloads without a server time cannot be put
     * in order and are always passed on.
     *
     * @param {string|number} key
     * @param {any} item
     * @returns {boolean}
     */
    isNewerThanPublished(key, item) {
        if (!item || typeof item['current_time'] !== 'number') {
            return true;
        }
        // Payloads serialized within the same millisecond describe the same state, so an equal time is
        // still published: dropping it could lose an update if the server clock is that coarse.
        if (this.lastPublishedAt[key] !== undefined && this.lastPublishedAt[key] > item['current_time']) {
            return false;
        }
        this.lastPublishedAt[key] = item['current_time'];
        return true;
    }

    /**
     * Hands data - polled, received as live event, or returned as the answer to a change this browser
     * made - to the widgets it is meant for, unless it was superseded in the meantime. Keyed channels
     * deliver a list of objects when polled, but a single object as a live event.
     *
     * @param {any} data
     */
    publishData(data) {
        if (this.config.collection) {
            this.publishCollection(data);
            return;
        }

        if (this.config.key_placeholder === null) {
            if (!this.isNewerThanPublished('', data)) {
                return;
            }
            this.registrations.forEach(registration => registration.onData(data));
            return;
        }

        const items = Array.isArray(data) ? data : [data];
        items.forEach(item => {
            if (!this.isNewerThanPublished(item.id, item)) {
                return;
            }
            this.registrations.forEach(registration => {
                if (registration.key === item.id) {
                    registration.onData(item);
                }
            });
        });
    }

    /**
     * A poll answers with the whole list and is therefore authoritative: whatever it does not contain
     * has left the collection. A live event carries a single member and is merged into it, appended if
     * it is one the widgets have not seen yet.
     *
     * A live event may also describe only part of a member ("partial"), which is how the votings
     * report a cast vote: only the counting changes, and everything the event does not mention stays
     * as the widgets have it - the reader's own state above all, which nobody else's vote affects.
     *
     * A member marked "removed" says that it has left the collection. Polls are authoritative about
     * that on their own, but a client with a live connection does not poll any more, so an object
     * that is deleted has to be able to say so.
     *
     * Every path replaces the collection with a new array rather than changing the one the widgets
     * were handed before. A widget that keeps what it is given and re-derives from it - as a Vue
     * component does - would otherwise not notice a live event at all: the array it holds would be
     * the array that was just changed, and nothing about it would look new. Polls happened to build
     * a new one anyway, which is why this only ever showed on installations running a Live server.
     *
     * @param {any} data
     */
    publishCollection(data) {
        if (Array.isArray(data)) {
            this.collection = data.map(item => {
                // A member that was superseded by something more recent keeps the newer version;
                // this is the same race a keyed channel guards against, member by member. A member
                // the collection does not hold any more was dropped by an event this poll predates,
                // so it stays dropped - a new one has no recorded time and passes the check anyway.
                const known = this.collection.find(existing => existing.id === item.id);
                if (this.isNewerThanPublished(item.id, item)) {
                    return item;
                }
                return known ?? null;
            }).filter(item => item !== null);
        } else {
            if (!this.isNewerThanPublished(data.id, data)) {
                return;
            }
            if (data.removed) {
                this.collection = this.collection.filter(existing => existing.id !== data.id);
                this.registrations.forEach(registration => registration.onData(this.collection));
                return;
            }
            const index = this.collection.findIndex(existing => existing.id === data.id);
            if (index === -1) {
                if (data.partial) {
                    // Too little to show anything with; the next poll brings the whole member
                    return;
                }
                this.collection = this.collection.concat([data]);
            } else {
                const { partial, ...fields } = data;
                this.collection = this.collection.map(
                    (member, memberIndex) => (memberIndex === index ? mergeMember(member, fields) : member)
                );
            }
        }

        this.registrations.forEach(registration => registration.onData(this.collection));
    }

    /**
     * @returns {Promise<Response>}
     */
    performRequest(url) {
        if (this.config.auth === 'jwt-optional' && !hasJwt()) {
            return apiFetch(url);
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
        // A channel is normally polled only while it has no live connection. The exception is a
        // channel whose live events deliberately leave something out: the votings omit the single
        // votes from every tally, being the one part of the payload that grows with the number of
        // people voting, so the administration - and only it, single votes reaching nobody else
        // while a voting runs - keeps polling to see them arrive.
        const liveIsEnough = this.liveConnected && !this.config.poll_while_live;

        return !this.givenUp && !liveIsEnough && !document.hidden &&
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
     * Hands the widgets the new state this browser received as the answer to a change it made. That
     * answer carries the same payload a poll would return and is newer than anything that was
     * requested before the change, so there is no reason to load the data again: publishing it gives
     * the other widgets showing the same object the new state right away, and marks the answers to
     * requests that are still running as superseded, so they cannot set the widgets back.
     *
     * @param {any} data
     */
    publishChange(data) {
        this.publishData(data);
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
            // The language is part of the destination: a live event carries every language of the
            // consultation, and the server sends the one this subscription asks for. It cannot be
            // taken from the JWT instead - the same user can read the site in two tabs in two
            // languages, and the server addresses subscriptions, not people.
            const topicUrl = '/' + channelConfig.role + '/' + liveConfig['installation'] + '/' + liveConfig['subdomain'] +
                '/' + liveConfig['consultation'] + '/' + encodeURIComponent(liveConfig['user_id']) + '/' + channelConfig.channel +
                '/' + encodeURIComponent(liveConfig['language']);
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
 * A widget that changes the state on the server passes the answer it got to `publishChange` instead
 * of applying it itself: that answer is the new state, and going through the channel hands it to the
 * other widgets showing the same object as well. `refreshNow` is only for loading the current data
 * when there is nothing to publish - after an action whose answer is not the state of this channel.
 *
 * @param {string} role
 * @param {string} channel
 * @param {{onData: function(any): void, onError?: function(Error): void, key?: string|number|null, intervalMs?: number|null, initialFetch?: boolean}} options
 * @returns {{setKey: function(string|number|null): void, setIntervalMs: function(number|null): void, refreshNow: function(): void, publishChange: function(any): void, unregister: function(): void}}
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
        publishChange: (data) => {
            channelObj.publishChange(data);
        },
        unregister: () => {
            channelObj.removeRegistration(registration);
        },
    };
}
