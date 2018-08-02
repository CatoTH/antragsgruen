import {Component, ElementRef} from '@angular/core';
import {WebsocketService} from "./websocket.service";
import {Collection} from "../classes/Collection";
import {Motion} from "../classes/Motion";
import {debounceTime} from 'rxjs/operators';
import {Amendment} from "../classes/Amendment";
import {CollectionItem} from "../classes/CollectionItem";
import {IMotion} from "../classes/IMotion";

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.css']
})
export class AppComponent {
    public log: string = '';
    public motionCollection: Collection<Motion> = new Collection<Motion>();
    public amendmentCollection: Collection<Amendment> = new Collection<Amendment>();
    public sortedItems: IMotion[];

    public constructor(private _websocket: WebsocketService, el: ElementRef) {
        this._websocket.debuglog$.subscribe((str) => {
            this.log += str + "\n";
        });
        this._websocket.authenticated$.subscribe((user) => {
            this._websocket.subscribeCollectionChannel(1, "motions", this.motionCollection);
            this._websocket.subscribeCollectionChannel(1, "amendments", this.amendmentCollection);
        });

        // Debounce: if a collection comes, don't recalculate the UI for each element
        this.motionCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));
        this.amendmentCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));

        this._websocket.connect(el.nativeElement.getAttribute("cookie"));
    }

    private recalcMotionList() {
        this.sortedItems = [];
        Object.keys(this.motionCollection.elements).forEach(key => {
            this.sortedItems.push(this.motionCollection.elements[key]);
        });
        Object.keys(this.amendmentCollection.elements).forEach(key => {
            this.sortedItems.push(this.amendmentCollection.elements[key]);
        });
        this.sortedItems.sort(IMotion.compareTitlePrefix);
    }

    private trackElement(index: number, element: CollectionItem) {
        return element ? element.getTrackId() : null;
    }
}
