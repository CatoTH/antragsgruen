import {Component, ElementRef} from '@angular/core';
import {WebsocketService} from "./websocket.service";

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css']
})
export class AppComponent {
    private log: string = '';

    public constructor(private _websocket: WebsocketService, el: ElementRef) {
        this._websocket.setDebugListener((str) => {
            this.log += str + "\n";
        });
        this._websocket.connect(el.nativeElement.getAttribute("cookie"));
        console.log("App component");
    }
}
