import {Component, ElementRef} from '@angular/core';
import {WebsocketService} from "./websocket.service";

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css']
})
export class AppComponent {
    public log: string = '';

    public constructor(private _websocket: WebsocketService, el: ElementRef) {
        this._websocket.debuglog$.subscribe((str) => {
            this.log += str + "\n";
        });
        this._websocket.authenticated$.subscribe((user) => {
            console.log("Auth", user);
            this._websocket.subscribeChannel(1, "motions");
        });
        this._websocket.connect(el.nativeElement.getAttribute("cookie"));

        console.log("App component");
    }
}
