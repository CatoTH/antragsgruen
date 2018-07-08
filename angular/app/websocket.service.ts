import {Injectable} from "@angular/core";
import {User} from "../classes/User";

@Injectable()
export class WebsocketService {
    private websocket: WebSocket;
    private authCookie: string;
    private debugListener;

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

    public setDebugListener(listener) {
        this.debugListener = listener;
    }

    private log(str: string): void {
        console.log(str);
        if (this.debugListener) {
            this.debugListener(str);
        }
    }

    private onopen() {
        this.websocket.send(JSON.stringify({"op": "auth", "auth": this.authCookie}));
        this.log('Connected to WebSocket server.');
    }

    private onClose() {
        this.log('Disconnected');
    }

    private onMessage(evt) {
        try {
            const msg = JSON.parse(evt.data);
            if (!msg['op']) {
                this.log('Invalid package: ' + evt.data);
                return;
            }
            switch (msg['op']) {
                case 'hello':
                    this.log('Got a friendly Hello from the server');
                    return;
                case 'auth_error':
                    this.log('Error authenticating: ' + msg['msg']);
                    return;
                case 'auth_success':
                    const user: User = JSON.parse(msg['user']);
                    console.log("User", user);
                    this.log("Authenticated: " + user.username);
                    return;
            }
        } catch (e) {
            console.warn("Invalid package: ", evt.data);
        }
    }

    private onError(evt) {
        this.log('Error occurred: ' + evt.data);
    }
}
