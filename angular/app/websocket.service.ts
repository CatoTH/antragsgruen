import {Injectable} from "@angular/core";
import {User} from "../classes/User";
import {Subject, ReplaySubject} from "rxjs";

@Injectable()
export class WebsocketService {
    private websocket: WebSocket;
    private authCookie: string;

    public authenticated$: Subject<User> = new ReplaySubject<User>(1);
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
        console.log("subscribe", this.websocket);
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
                    const user: User = JSON.parse(msg['user']);
                    this.debuglog$.next("Authenticated: " + user.username);
                    this.authenticated$.next(user);
                    console.log("next");
                    return;
            }
        } catch (e) {
            console.warn("Invalid package: ", evt.data);
        }
    }

    private onError(evt) {
        this.debuglog$.next('Error occurred: ' + evt.data);
    }
}
