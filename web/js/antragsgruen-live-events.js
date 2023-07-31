if (!document.head.querySelector("meta[name=user-jwt]") || !document.head.querySelector("meta[name=live-config]"))
{
    console.warn("JWT and Live configuration needs to be set for live events to work");
} else {
    const jwt = document.head.querySelector("meta[name=user-jwt]").content;
    const liveConfig = JSON.parse(document.head.querySelector("meta[name=live-config]").content);

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
        console.log("Connected to Live Server");
        stompClient.subscribe('/topic/' + liveConfig['subdomain'] + '/' + liveConfig['consultation'] + '/greetings', (greeting) => {
            console.log("GLOBAL: " + JSON.parse(greeting.body).content);
        });
        stompClient.subscribe('/user/' + liveConfig['subdomain'] + '/' + liveConfig['consultation'] + '/' + encodeURIComponent(liveConfig['user_id']) + '/update', (message) => {
            console.log("USER 1: " + JSON.parse(message.body).content);
        });
    };

    stompClient.onWebSocketError = (error) => {
        console.error('Error with websocket', error);
    };

    stompClient.onStompError = (frame) => {
        console.error('Broker reported error: ' + frame.headers['message']);
        console.error('Additional details: ' + frame.body);
    };

    stompClient.activate();
}
