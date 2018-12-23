import {User} from "../classes/User";
import {Subject, ReplaySubject} from "rxjs";
import {Collection} from "../classes/Collection";

export class WebsocketService {
    private websocket: WebSocket;
    private authCookie: string;
    private active = false;
    private subdomain: string;
    private path: string;

    public authenticated$: Subject<User> = new ReplaySubject<User>(1);
    public debuglog$: Subject<string> = new Subject<string>();

    private collections: { [id: string]: Collection<any> } = {};

    constructor() {
    }

    public setSubdomainPath(subdomain: string, path: string) {
        this.subdomain = subdomain;
        this.path = path;
    }

    public connect(authCookie: string, port: number) {
        this.authCookie = authCookie;
        this.active = false;
        this.websocket = new WebSocket('ws://' + window.location.host + ':' + port.toString());
        this.websocket.onopen = this.onopen.bind(this);
        this.websocket.onclose = this.onClose.bind(this);
        this.websocket.onmessage = this.onMessage.bind(this);
        this.websocket.onerror = this.onError.bind(this);
    }

    public subscribeCollectionChannel(consultationId: number, channel: string, collection: Collection<any>) {
        this.websocket.send(JSON.stringify({
            "op": "subscribe",
            "consultation": consultationId,
            "channel": channel,
        }));
        this.collections[channel] = collection;
    }

    private onopen() {
        this.active = true;
        this.websocket.send(JSON.stringify({
            "op": "auth",
            "auth": this.authCookie,
            "subdomain": this.subdomain,
            "path": this.path,
        }));
        this.debuglog$.next('Connected to WebSocket server.');
    }

    private onClose() {
        this.debuglog$.next('Disconnected');
        this.active = false;
    }

    private onMessage(evt) {
        try {
            const msg = JSON.parse(evt.data);
            if (!msg['op']) {
                this.debuglog$.next('Invalid package (1): ' + evt.data);
                return;
            }
            switch (msg['op']) {
                case 'hello':
                    this.debuglog$.next('Got a friendly Hello from the server');
                    return;
                case 'auth_error':
                    this.debuglog$.next('Error authenticating: ' + msg['msg']);
                    return;
                case 'auth_success':
                    const user: User = msg['user'];
                    this.debuglog$.next("Authenticated: " + user.username);
                    this.authenticated$.next(user);
                    console.log("next");
                    return;
                case 'object':
                    this.debuglog$.next("Got object: " + msg['type'] + ": " + JSON.stringify(msg['object']));
                    this.collections[msg['type']].setElement(msg['object']);
                    return;
                case 'object-delete':
                    this.debuglog$.next("Deleting object: " + msg['id']);
                    this.onDeleteObject(msg['type'], msg['id']);
                    return;
                case 'object-collection':
                    this.debuglog$.next("Got collection: " + msg['type'] + ": " + JSON.stringify(msg['objects']));
                    this.collections[msg['type']].setElements(msg['objects']);
                    return;
            }
        } catch (e) {
            console.warn("Invalid package (2): ", evt.data, e);
        }
    }

    private onError(evt) {
        console.error(evt);
        if (evt.data) {
            this.debuglog$.next('Error occurred: ' + evt.data);
        } else if (!this.active) {
            this.debuglog$.next('Error connecting');
        } else {
            this.debuglog$.next('Error');
        }
    }

    private onDeleteObject(type, objectId: string) {
        this.collections[type].deleteElement(objectId);
    }
}
