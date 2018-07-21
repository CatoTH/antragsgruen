import {Injectable} from "@angular/core";
import {User} from "../classes/User";
import {Subject, ReplaySubject} from "rxjs";
import {Motion} from "../classes/Motion";

@Injectable()
export class WebsocketService {
    private websocket: WebSocket;
    private authCookie: string;

    public authenticated$: Subject<User> = new ReplaySubject<User>(1);
    public motions$: Subject<Motion> = new ReplaySubject<Motion>(1);
    public debuglog$: Subject<string> = new Subject<string>();

    constructor() {
    }

    public connect(authCookie: string) {
        this.authCookie = authCookie;
        this.websocket = new WebSocket('ws://127.0.0.1:9501');
        this.websocket.onopen = this.onopen.bind(this);
        this.websocket.onclose = this.onClose.bind(this);
        this.websocket.onmessage = this.onMessage.bind(this);
        this.websocket.onerror = this.onError.bind(this);
    }

    public subscribeChannel(consultationId: number, channel: string) {
        this.websocket.send(JSON.stringify({
            "op": "subscribe",
            "consultation": consultationId,
            "channel": channel,
        }));
    }

    private onopen() {
        this.websocket.send(JSON.stringify({
            "op": "auth",
            "auth": this.authCookie,
        }));
        this.debuglog$.next('Connected to WebSocket server.');
    }

    private onClose() {
        this.debuglog$.next('Disconnected');
    }

    private onMessage(evt) {
        try {
            const msg = JSON.parse(evt.data);
            if (!msg['op']) {
                this.debuglog$.next('Invalid package: ' + evt.data);
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
                    this.debuglog$.next("Got object: " + msg['type'] + ": " + JSON.stringify(msg['data']));
                    this.onGotObject(msg['type'], msg['data']);
                    return;
                case 'object-collection':
                    this.debuglog$.next("Got collection: " + msg['type'] + ": " + JSON.stringify(msg['data']));
                    this.onGotObjectCollection(msg['type'], msg['data']);
                    return;
            }
        } catch (e) {
            console.warn("Invalid package: ", evt.data);
        }
    }

    private onError(evt) {
        this.debuglog$.next('Error occurred: ' + evt.data);
    }

    private onGotObject(type, data) {
        switch (type) {
            case 'motions':
                this.motions$.next(new Motion(data));
                break;
        }
    }

    private onGotObjectCollection(type, data: object[]) {
        data.forEach((dat) => {
            this.onGotObject(type, dat);
        });
    }
}
