import {Component, ElementRef} from '@angular/core';
import {WebsocketService} from "./websocket.service";
import {Collection} from "../classes/Collection";
import {Motion} from "../classes/Motion";

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css']
})
export class AppComponent {
    public log: string = '';
    public motionCollection: Collection = new Collection();
    public sortedMotions: Motion[];

    public constructor(private _websocket: WebsocketService, el: ElementRef) {
        this._websocket.debuglog$.subscribe((str) => {
            this.log += str + "\n";
        });
        this._websocket.authenticated$.subscribe((user) => {
            this._websocket.subscribeChannel(1, "motions");
        });
        this._websocket.motions$.subscribe((motion) => {
            this.motionCollection.setElement(motion);
            this.recalcMotionList();
        });
        this._websocket.connect(el.nativeElement.getAttribute("cookie"));
    }

    private recalcMotionList() {
        this.sortedMotions = Object.keys(this.motionCollection.elements).map(key => this.motionCollection.elements[key]);
        console.log(this.motionCollection);
        console.log(this.sortedMotions);
    }
}
