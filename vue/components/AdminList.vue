<template>
    <div>
        <div class="greeting">Hello {{name}}{{exclamationMarks}}</div>
        <button @click="decrement">-</button>
        <button @click="increment">+</button>
        <pre>{{ debugLog }}</pre>
    </div>
</template>

<script lang="ts">
import { Vue, Component, Prop } from "vue-property-decorator";
import {WebsocketService} from "./websocket.service";
import {Collection} from "../classes/Collection";
import {Motion} from "../classes/Motion";
import {Amendment} from "../classes/Amendment";
import {IMotion} from "../classes/IMotion";
@Component
export default class AdminList extends Vue {
    @Prop() name!: string;
    @Prop() initialEnthusiasm!: number;
    @Prop() subdomain!: string;
    @Prop() path!: string;
    @Prop() cookie!: string;
    @Prop() wsPort!: number;
    enthusiasm = this.initialEnthusiasm;

    private _websocket: WebsocketService;
    private log = '';

    public motionCollection: Collection<Motion> = new Collection<Motion>(<any>Motion);
    public amendmentCollection: Collection<Amendment> = new Collection<Amendment>(<any>Amendment);
    public allItems: IMotion[];
    public sortedFilteredItems: IMotion[];

    public searchPrefix = "";
    public searchTitle = "";
    public searchInitiator = "";
    private filters: { [filterId: string]: (IMotion) => boolean } = {};

    public hasTopics = true;
    public hasProposedProcedure = false;

    constructor(options) {
        super(options);
        this.initWebsocket();
    }

    private initWebsocket() {
        this._websocket = new WebsocketService();
        this._websocket.setSubdomainPath(this.subdomain, this.path);
        this._websocket.debuglog$.subscribe((str) => {
            this.log += str + "\n";
        });
        this._websocket.authenticated$.subscribe((user) => {
            this._websocket.subscribeCollectionChannel(1, 'motions', this.motionCollection);
            this._websocket.subscribeCollectionChannel(1, 'amendments', this.amendmentCollection);
        });
        this._websocket.connect(this.cookie, this.wsPort);
    }

    increment() {
        this.enthusiasm++;
    }
    decrement() {
        if (this.enthusiasm > 1) {
            this.enthusiasm--;
        }
    }
    get exclamationMarks(): string {
        return Array(this.enthusiasm + 1).join('!');
    }
    get debugLog(): string {
        return this.log;
    }
}
</script>
