if (!document.head.querySelector("meta[name=user-jwt]") || !document.head.querySelector("meta[name=live-config]"))
{
    console.warn("JWT and Live configuration needs to be set for live events to work");
} else {
    const jwt = document.head.querySelector("meta[name=user-jwt]").content;
    const liveConfig = JSON.parse(document.head.querySelector("meta[name=live-config]").content);

    class AntragsgruenLiveEvents {
        listeners = {
            user: {
                speech: []
            },
            admin: {
                speech: []
            }
        };
        connected = {
            user: {
                speech: false
            },
            admin: {
                speech: false
            }
        };

        constructor() {}

        registerListener(role, channel, listener) {
            this.listeners[role][channel].push(listener);
            if (this.listeners[role][channel]) {
                listener(true, null);
            }
        }

        publishEvent(role, channel, event) {
            this.listeners[role][channel].forEach(listener => {
                listener(null, event);
            });
        }

        onConnected(role, channel) {
            this.connected[role][channel] = true;
            this.listeners[role][channel].forEach(listener => {
                listener(true, null);
            });
        }

        onDisconnected(namespace) {
            this.connected[namespace] = false;
            this.listeners[namespace].forEach(listener => {
                listener(false, null);
            });
        }
    }

    window['ANTRAGSGRUEN_LIVE_EVENTS'] = new AntragsgruenLiveEvents();

    const stompClient = new StompJs.Client({
        brokerURL: liveConfig['uri'],

        debug: function (str) {
            // console.log(str);
        },
        reconnectDelay: 5000,
        heartbeatIncoming: 4000,
        heartbeatOutgoing: 4000,
        connectHeaders: {
            jwt: jwt
        }
    });

    stompClient.onConnect = (frame) => {
        console.info("Connected to Antragsgrün Live Server");
        liveConfig['subscriptions'].forEach(subscription => {
            const topicUrl = '/' + subscription['role'] + '/' + liveConfig['subdomain'] + '/' + liveConfig['consultation'] +
                '/' + encodeURIComponent(liveConfig['user_id']) + '/' + subscription['channel'];
            stompClient.subscribe(topicUrl, message => {
                window['ANTRAGSGRUEN_LIVE_EVENTS'].publishEvent(subscription.role, subscription.channel, JSON.parse(message.body));
            });
            window['ANTRAGSGRUEN_LIVE_EVENTS'].onConnected(subscription.role, subscription.channel);
        });
    };

    stompClient.onWebSocketError = (error) => {
        console.error('Error with websocket', error);
        liveConfig['subscriptions'].forEach(subscription => {
            window['ANTRAGSGRUEN_LIVE_EVENTS'].onDisconnected(subscription.role, subscription.channel);
        })
    };

    stompClient.onStompError = (frame) => {
        console.error('Broker reported error: ' + frame.headers['message']);
        console.error('Additional details: ' + frame.body);
        liveConfig['subscriptions'].forEach(subscription => {
            window['ANTRAGSGRUEN_LIVE_EVENTS'].onDisconnected(subscription.role, subscription.channel);
        });
    };

    stompClient.activate();
}
