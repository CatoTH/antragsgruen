import {Component, ElementRef} from '@angular/core';
import {WebsocketService} from "./websocket.service";
import {Collection} from "../classes/Collection";
import {Motion} from "../classes/Motion";
import {debounceTime} from 'rxjs/operators';
import {Amendment} from "../classes/Amendment";
import {HttpClient, HttpHeaders} from "@angular/common/http";
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
    private readonly ajaxBackendUrl: string;
    private readonly csrfParam: string;
    private readonly csrfToken: string;
    public readonly linkTemplates: { [key: string]: string };

    public constructor(private _websocket: WebsocketService,
                       private el: ElementRef<Element>,
                       private _http: HttpClient) {
        // Debounce: if a collection comes, don't recalculate the UI for each element
        this.motionCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));
        this.amendmentCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));

        if (el.nativeElement.getAttribute('ws-port')) {
            this.initWebsocket(el);
        }
        this.ajaxBackendUrl = el.nativeElement.getAttribute('ajax-backend');
        this.csrfToken = el.nativeElement.getAttribute('csrf-token');
        this.csrfParam = el.nativeElement.getAttribute('csrf-param');
        this.linkTemplates = JSON.parse(el.nativeElement.getAttribute('link-templates'));
        let initData = JSON.parse(el.nativeElement.getAttribute('init-collections'));
        this.motionCollection.setElements(initData['motions']);
        this.amendmentCollection.setElements(initData['amendments']);
    }

    private initWebsocket(el: ElementRef) {
        this._websocket.debuglog$.subscribe((str) => {
            this.log += str + "\n";
        });
        this._websocket.authenticated$.subscribe((user) => {
            this._websocket.subscribeCollectionChannel(1, 'motions', this.motionCollection);
            this._websocket.subscribeCollectionChannel(1, 'amendments', this.amendmentCollection);
        });
        this._websocket.connect(
            el.nativeElement.getAttribute('cookie'),
            el.nativeElement.getAttribute('ws-port')
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

    private callBackend(data: URLSearchParams) {
        data.set(this.csrfParam, this.csrfToken);
        return this._http
            .post(this.ajaxBackendUrl, data.toString(), {
                headers: new HttpHeaders().set('Content-Type', 'application/x-www-form-urlencoded')
            });
    }

    public motionScreen(item: Motion, $event) {
        $event.preventDefault();

        let params = new URLSearchParams();
        params.set('operation', 'motionScreen');
        params.set('motionId[]', item.id);
        this.callBackend(params).subscribe((returnValue) => {
            console.log(returnValue);
        });
    }

    public motionUnscreen(item: Motion, $event) {
        $event.preventDefault();

        let params = new URLSearchParams();
        params.set('operation', 'motionUnscreen');
        params.set('motionId[]', item.id);
        this.callBackend(params).subscribe((returnValue) => {
            console.log(returnValue);
        });
    }

    public motionCreateFromTemplate(item: Motion, $event) {
        $event.preventDefault();
    }

    public motionDelete(item: Motion, $event) {
        $event.preventDefault();
    }

    public amendmentScreen(item: Amendment, $event) {
        $event.preventDefault();
    }

    public amendmentUnscreen(item: Amendment, $event) {
        $event.preventDefault();
    }

    public amendmentCreateFromTemplate(item: Amendment, $event) {
        $event.preventDefault();
    }

    public amendmentDelete(item: Amendment, $event) {
        $event.preventDefault();
    }
}
