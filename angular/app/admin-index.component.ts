import {Component, ElementRef} from '@angular/core';
import {WebsocketService} from "./websocket.service";
import {Collection} from "../classes/Collection";
import {Motion} from "../classes/Motion";
import {debounceTime} from 'rxjs/operators';
import {Amendment} from "../classes/Amendment";
import {CollectionItem} from "../classes/CollectionItem";
import {IMotion} from "../classes/IMotion";

@Component({
    selector: 'admin-index',
    templateUrl: './admin-index.component.html',
})
export class AdminIndexComponent {
    public log: string = '';
    public motionCollection: Collection<Motion> = new Collection<Motion>(Motion);
    public amendmentCollection: Collection<Amendment> = new Collection<Amendment>(Amendment);
    public sortedItems: IMotion[];
    private ajaxBackendUrl: string;
    public linkTemplates: {[key: string]: string};

    public constructor(private _websocket: WebsocketService, el: ElementRef<Element>) {
        // Debounce: if a collection comes, don't recalculate the UI for each element
        this.motionCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));
        this.amendmentCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));

        if (el.nativeElement.getAttribute("ws-port")) {
            this.initWebsocket(el);
        }
        this.ajaxBackendUrl = el.nativeElement.getAttribute("ajax-backend");
        this.linkTemplates = JSON.parse(el.nativeElement.getAttribute("link-templates"));
        let initData = JSON.parse(el.nativeElement.getAttribute("init-collections"));
        this.motionCollection.setElements(initData['motions']);
        this.amendmentCollection.setElements(initData['amendments']);
    }

    private initWebsocket(el: ElementRef) {
        this._websocket.debuglog$.subscribe((str) => {
            this.log += str + "\n";
        });
        this._websocket.authenticated$.subscribe((user) => {
            this._websocket.subscribeCollectionChannel(1, "motions", this.motionCollection);
            this._websocket.subscribeCollectionChannel(1, "amendments", this.amendmentCollection);
        });
        this._websocket.connect(
            el.nativeElement.getAttribute("cookie"),
            el.nativeElement.getAttribute("ws-port")
        );
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

    public trackElement(index: number, element: CollectionItem) {
        return element ? element.getTrackId() : null;
    }
}
