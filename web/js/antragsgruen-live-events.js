if (!document.head.querySelector("meta[name=user-jwt]") || !document.head.querySelector("meta[name=live-config]"))
{
    console.warn("JWT and Live configuration needs to be set for live events to work");
} else {
    const jwt = document.head.querySelector("meta[name=user-jwt]").content;
    const liveConfig = JSON.parse(document.head.querySelector("meta[name=live-config]").content);

    class AntragsgruenLiveEvents {
        listeners = {
            speech: []
        };
        connected = {
            speech: false
        };

        constructor() {}

        registerListener(namespace, listener) {
            this.listeners[namespace].push(listener);
            if (this.listeners[namespace]) {
                listener(true, null);
            }
        }

        publishEvent(namespace, event) {
            this.listeners[namespace].forEach(listener => {
                listener(null, event);
            });
        }

        onConnected(namespace) {
            this.connected[namespace] = true;
            this.listeners[namespace].forEach(listener => {
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
        /*
        stompClient.subscribe('/topic/' + liveConfig['subdomain'] + '/' + liveConfig['consultation'] + '/update', (greeting) => {
            console.log("GLOBAL", JSON.parse(greeting.body));
        });
        stompClient.subscribe('/user/' + liveConfig['subdomain'] + '/' + liveConfig['consultation'] + '/' + encodeURIComponent(liveConfig['user_id']) + '/default', (message) => {
            console.log("USER DEFAULT", JSON.parse(message.body));
        });
         */
        stompClient.subscribe('/user/' + liveConfig['subdomain'] + '/' + liveConfig['consultation'] + '/' + encodeURIComponent(liveConfig['user_id']) + '/speech', (message) => {
            window['ANTRAGSGRUEN_LIVE_EVENTS'].publishEvent('speech', JSON.parse(message.body));
        });
        window['ANTRAGSGRUEN_LIVE_EVENTS'].onConnected('speech');
    };

    stompClient.onWebSocketError = (error) => {
        console.error('Error with websocket', error);
        window['ANTRAGSGRUEN_LIVE_EVENTS'].onDisconnected('speech');
    };

    stompClient.onStompError = (frame) => {
        console.error('Broker reported error: ' + frame.headers['message']);
        console.error('Additional details: ' + frame.body);
        window['ANTRAGSGRUEN_LIVE_EVENTS'].onDisconnected('speech');
    };

    stompClient.activate();
}
