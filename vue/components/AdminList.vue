<template>
    <section>
        <section class="motionListSearchForm fuelux">
            <label>
                Antragsnr.:<br>
                <input type="text" class="form-control inputPrefix" v-bind:value="searchPrefix"
                       @change="searchPrefixChange" @keyup="searchPrefixChange">
            </label>
            <label>
                Titel:<br>
                <input type="text" class="form-control inputPrefix" v-bind:value="searchTitle"
                       @change="searchTitleChange" @keyup="searchTitleChange">
            </label>
            <label>Status:<br>
                <select-list :items="getAvailableStatusItems()"
                             @selected="setStatusItem"/>
            </label>
            <label>Initiator:<br>
                <input type="text" class="form-control inputPrefix" v-bind:value="searchInitiator"
                       @change="searchInitiatorChange" @keyup="searchInitiatorChange">
            </label>
            <label v-if="hasTopics">Thema:<br>
                <!--<selectlist v-bind:items="getAvailableTagsItems()" selected=""
                            @select="setTagItem"></selectlist>-->
            </label>
        </section>
        <table class="adminMotionTable">
            <thead>
            <tr>
                <th></th>
                <th i18n="admin-index column header">Type</th>
                <th i18n="admin-index column header">Code</th>
                <th i18n="admin-index column header">Title</th>
                <th i18n="admin-index column header">Status</th>
                <th i18n="admin-index column header">Initiators</th>
                <th i18n="admin-index column header" v-if="hasTopics">Topic</th>
                <th i18n="admin-index column header">Export</th>
                <th i18n="admin-index column header">Action</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="item in sortedFilteredItems()">
                <tr v-if="item.type === 'motion'" :key="item.getTrackId()">
                    <td><input type="checkbox"></td>
                    <td i18n="admin-index motion indicator">Mot</td>
                    <td><a v-bind:href="item.getLink('motion/view', linkTemplatesArr)"
                           v-html="getHighlightedPrefix(item)"></a></td>
                    <td><a v-bind:href="item.getLink('admin/motion/update', linkTemplatesArr)"
                           v-html="getHighlightedTitle(item)"></a></td>
                    <td class="statusCol">{{ getStatusString(item) }}</td>
                    <td class="initiatorCol" v-html="getHighlightedInitiator(item)"></td>
                    <td class="tagsCol" v-if="hasTopics">
                        <ul>
                            <li v-for="tag in item.tags">
                                {{ tag.title }}
                            </li>
                        </ul>
                    </td>
                    <td class="exportCol">
                        <a v-bind:href="item.getLink('motion/pdf', linkTemplatesArr)" class="pdf"
                           i18n="admin-index export">PDF</a> /
                        <a v-bind:href="item.getLink('motion/pdfamendcollection', linkTemplatesArr)" class="pdf"
                           i18n="admin-index export">PDF + Amd.</a> /
                        <a v-bind:href="item.getLink('motion/odt', linkTemplatesArr)" class="odt"
                           i18n="admin-index export">ODT</a> /
                        <a v-bind:href="item.getLink('motion/plainhtml', linkTemplatesArr)" class="html"
                           i18n="admin-index export">HTML</a>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                    aria-expanded="false">
                                Action
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li v-if="item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="motionScreen(item, $event)" class="screen"
                                       i18n="admin-index action dropdown">Screen</a>
                                </li>
                                <li v-if="!item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="motionUnscreen(item, $event)" class="unscreen"
                                       i18n="admin-index action dropdown">Un-screen</a>
                                </li>
                                <li>
                                    <a tabindex="-1" v-bind:href="item.getLink('motion/clone', linkTemplatesArr)"
                                       class="asTemplate" target="_blank"
                                       i18n="admin-index action dropdown">Create a new motion based on this one</a>
                                </li>
                                <li>
                                    <a tabindex="-1" href="#" @click="motionDelete(item, $event)" class="delete"
                                       i18n="admin-index action dropdown">Delete</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <tr v-if="item.type === 'amendment'" :key="item.getTrackId()">
                    <td><input type="checkbox"></td>
                    <td i18n="admin-index amendment indicator">Amd</td>
                    <td><a v-bind:href="item.getLink('amendment/view', linkTemplatesArr)">
                        &#8627;
                        <span v-html="getHighlightedPrefix(item)"></span>
                    </a></td>
                    <td><a v-bind:href="item.getLink('admin/amendment/update', linkTemplatesArr)"
                           v-html="getHighlightedTitle(item)"></a></td>
                    <td class="statusCol">{{ getStatusString(item) }}</td>
                    <td class="initiatorCol" v-html="getHighlightedInitiator(item)"></td>
                    <td class="tagsCol" v-if="hasTopics"></td>
                    <td>
                        <a v-bind:href="item.getLink('amendment/pdf', linkTemplatesArr)" class="pdf"
                           i18n="admin-index export">PDF</a>
                        /
                        <a v-bind:href="item.getLink('amendment/odt', linkTemplatesArr)" class="odt"
                           i18n="admin-index export">ODT</a>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                    aria-expanded="false">
                                Action
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li v-if="item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="amendmentScreen(item, $event)" class="screen"
                                       i18n="admin-index action dropdown">Screen</a>
                                </li>
                                <li v-if="!item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="amendmentUnscreen(item, $event)" class="unscreen"
                                       i18n="admin-index action dropdown">Un-screen</a>
                                </li>
                                <li>
                                    <a tabindex="-1" v-bind:href="item.getLink('amendment/clone', linkTemplatesArr)"
                                       class="asTemplate" target="_blank"
                                       i18n="admin-index action dropdown">Create a new amendment based on this one</a>
                                </li>
                                <li>
                                    <a tabindex="-1" href="#" @click="amendmentDelete(item, $event)" class="delete"
                                       i18n="admin-index action dropdown">Delete</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>

        <pre>{{ debugLog }}</pre>
    </section>
</template>

<script lang="ts">
    import {Vue, Component, Prop} from "vue-property-decorator";
    import {WebsocketService} from "./websocket.service";
    import {Collection} from "../classes/Collection";
    import {Motion} from "../classes/Motion";
    import {Amendment} from "../classes/Amendment";
    import {IMotion} from "../classes/IMotion";
    import {Translations} from "../classes/Translations";
    import {SelectlistItem} from "../../angular/app/selectlist.component";
    import {STATUS} from "../classes/Status";
    import {CollectionItem} from "../classes/CollectionItem";
    import axios from 'axios';
    import {debounceTime} from 'rxjs/operators';
    import SelectList from "./SelectList.vue";

    interface MotionWithAmendments {
        motion: Motion;
        amendments: Amendment[];
    }

    @Component({
        components: {
            "select-list": SelectList
        }
    })
    export default class AdminList extends Vue {
        @Prop() subdomain!: string;
        @Prop() ajaxBackendUrl!: string;
        @Prop() path!: string;
        @Prop() cookie!: string;
        @Prop() wsPort!: number;
        @Prop() csrfParam!: string;
        @Prop() csrfToken!: string;
        @Prop() linkTemplates!: string;
        @Prop() initCollections!: string;

        private _websocket: WebsocketService;
        private log = '';
        public linkTemplatesArr: { [key: string]: string };

        public motionCollection: Collection<Motion> = new Collection<Motion>(<any>Motion);
        public amendmentCollection: Collection<Amendment> = new Collection<Amendment>(<any>Amendment);
        allItems: IMotion[];
        public _sortedFilteredItems: IMotion[];

        public searchPrefix = "";
        public searchTitle = "";
        public searchInitiator = "";
        private filters: { [filterId: string]: (IMotion) => boolean } = {};

        private _hasTopics = true;
        public hasProposedProcedure = false;

        created() {
            const data = JSON.parse(this.initCollections);

            this.motionCollection.setElements(data['motions']);
            this.amendmentCollection.setElements(data['amendments']);
            this.motionCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));
            this.amendmentCollection.changed$.pipe(debounceTime(1)).subscribe(this.recalcMotionList.bind(this));

            this.linkTemplatesArr = JSON.parse(this.linkTemplates);

            this.recalcMotionList();
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

        get debugLog(): string {
            return this.log;
        }

        get hasTopics(): boolean {
            return this._hasTopics;
        }

        public sortedFilteredItems(): IMotion[] {
            return this._sortedFilteredItems;
        }

        public recalcMotionList() {
            this.allItems = [];
            Object.keys(this.motionCollection.elements).forEach(key => {
                this.allItems.push(this.motionCollection.elements[key]);
            });
            Object.keys(this.amendmentCollection.elements).forEach(key => {
                this.allItems.push(this.amendmentCollection.elements[key]);
            });

            this._sortedFilteredItems = this.allItems.filter((item: IMotion) => {
                let matches = true;
                Object.keys(this.filters).forEach(key => {
                    if (!this.filters[key](item)) {
                        matches = false;
                    }
                });
                return matches;
            });

            this._sortedFilteredItems = AdminList.sortMotionsAmendmentsByPrefix(this._sortedFilteredItems);

            this.$forceUpdate();
        }

        private static sortMotionsAmendmentsByPrefix(items: IMotion[]): IMotion[] {
            let byMotion: { [id: string]: MotionWithAmendments } = {};
            let amendmentsWithoutMotion: Amendment[] = [];
            let sortedItems = items.sort(IMotion.compareTitlePrefix);

            sortedItems.filter(item => item['motionId'] === undefined).forEach((item: Motion) => {
                byMotion[item.id] = {motion: item, amendments: []};
            });
            sortedItems.filter(item => item['motionId'] !== undefined).forEach((item: Amendment) => {
                if (byMotion[item.motionId] !== undefined) {
                    byMotion[item.motionId].amendments.push(item);
                } else {
                    amendmentsWithoutMotion.push(item);
                }
            });

            let sorted: IMotion[] = [];
            Object.keys(byMotion).forEach(entryId => {
                sorted.push(byMotion[entryId].motion);
                sorted.splice(sorted.length, 0, ...byMotion[entryId].amendments);
            });
            sorted.splice(sorted.length, 0, ...amendmentsWithoutMotion);

            return sorted;
        }

        public trackElement(index: number, element: CollectionItem) {
            return element ? element.getTrackId() : null;
        }

        private callBackend(data: URLSearchParams): Promise<any> {
            data.set(this.csrfParam, this.csrfToken);
            return axios.post(
                this.ajaxBackendUrl,
                data,
                {
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    }
                }
            );
        }

        public motionScreen(item: Motion, $event) {
            $event.preventDefault();

            let params = new URLSearchParams();
            params.set('operation', 'motionScreen');
            params.set('motionId[]', item.id);
            this.callBackend(params).then((returnValue) => {
                console.log(returnValue);
            });
        }

        public motionUnscreen(item: Motion, $event) {
            $event.preventDefault();

            let params = new URLSearchParams();
            params.set('operation', 'motionUnscreen');
            params.set('motionId[]', item.id);
            this.callBackend(params).then((returnValue) => {
                console.log(returnValue);
            });
        }

        public motionDelete(item: Motion, $event) {
            $event.preventDefault();

            let params = new URLSearchParams();
            params.set('operation', 'motionDelete');
            params.set('motionId[]', item.id);
            this.callBackend(params).then((returnValue) => {
                console.log(returnValue);
            });
        }

        public amendmentScreen(item: Amendment, $event) {
            $event.preventDefault();

            let params = new URLSearchParams();
            params.set('operation', 'amendmentScreen');
            params.set('amendmentId[]', item.id);
            this.callBackend(params).then((returnValue) => {
                console.log(returnValue);
            });
        }

        public amendmentUnscreen(item: Amendment, $event) {
            $event.preventDefault();

            let params = new URLSearchParams();
            params.set('operation', 'amendmentUnscreen');
            params.set('amendmentId[]', item.id);
            this.callBackend(params).then((returnValue) => {
                console.log(returnValue);
            });
        }

        public amendmentDelete(item: Amendment, $event) {
            $event.preventDefault();

            let params = new URLSearchParams();
            params.set('operation', 'amendmentDelete');
            params.set('amendmentId[]', item.id);
            this.callBackend(params).then((returnValue) => {
                console.log(returnValue);
            });
        }

        private regexescape(str: string): RegExp {
            return new RegExp(str.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&'), "i");
        }

        public getHighlightedTitle(item: IMotion): string {
            let html = item.getTitle().replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;');
            if (this.searchTitle !== '') {
                let search = this.searchTitle.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;');
                html = html.replace(this.regexescape(search), (match) => {
                    return '<mark>' + match + '</mark>';
                });
            }
            return html;
        }

        public getHighlightedInitiator(item: IMotion): string {
            let html = item.getInitiatorName().replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;');
            if (this.searchInitiator !== '') {
                let search = this.searchInitiator.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;');
                html = html.replace(this.regexescape(search), (match) => {
                    return '<mark>' + match + '</mark>';
                });
            }
            return html;
        }

        public getHighlightedPrefix(item: IMotion): string {
            let html = item.titlePrefix.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;');
            if (this.searchPrefix !== '') {
                let search = this.searchPrefix.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;');
                html = html.replace(this.regexescape(search), (match) => {
                    return '<mark>' + match + '</mark>';
                });
            }
            if (html === '') {
                html = '-';
            }
            return html;
        }

        public getStatusString(item: IMotion): string {
            let status = Translations.getStatusName(item.status);
            if (item.status === STATUS.COLLECTING_SUPPORTERS) {
                status += ' (' + item.supporters.length.toString(10) + ')';
            }
            return status;
        }

        private sortAndAddZeroItems(items: SelectlistItem[]): SelectlistItem[] {
            items.sort((obj1, obj2) => {
                if (obj2.num > obj1.num) {
                    return 1;
                } else if (obj2.num < obj1.num) {
                    return -1;
                } else {
                    return 0;
                }
            });
            items.unshift({
                id: "0",
                title: Translations.get('admin', 'filter_na'),
                num: null,
            });
            return items;
        }

        public getAvailableStatusItems(): SelectlistItem[] {
            if (!this.allItems) {
                return [];
            }
            let statuses = {};
            this.allItems.forEach((item) => {
                const status = item.status.toString();
                if (statuses[status] === undefined) {
                    statuses[status] = 0;
                }
                statuses[status]++;
            });

            return this.sortAndAddZeroItems(Object.keys(statuses).map((status: string) => {
                return {
                    id: status,
                    title: Translations.getStatusName(parseInt(status)),
                    num: statuses[status],
                }
            }));
        }

        public getAvailableTagsItems(): SelectlistItem[] {
            if (!this.allItems) {
                return [];
            }
            let tags = {};
            this.allItems.forEach((item) => {
                item.tags.forEach((tag) => {
                    const tagId = tag.id.toString();
                    if (tags[tagId] === undefined) {
                        tags[tagId] = 0;
                    }
                    tags[tagId]++;
                });
            });

            return this.sortAndAddZeroItems(Object.keys(tags).map((tag: string) => {
                return {
                    id: tag,
                    title: Translations.getTagName(tag),
                    num: tags[tag],
                }
            }));
        }

        public searchPrefixChange($ev: KeyboardEvent) {
            const element = <HTMLInputElement>$ev.target;
            if (element.value === this.searchPrefix) {
                return;
            }
            this.searchPrefix = element.value;
            if (this.searchPrefix === '') {
                delete this.filters['prefix'];
            } else {
                this.filters['prefix'] = (motion: IMotion) => {
                    return motion.titlePrefix.toLowerCase().indexOf(this.searchPrefix.toLowerCase()) !== -1;
                };
            }
            this.recalcMotionList();
        }

        public searchTitleChange($ev) {
            if ($ev.currentTarget.value === this.searchTitle) {
                return;
            }
            this.searchTitle = $ev.currentTarget.value;
            if (this.searchTitle === '') {
                delete this.filters['title'];
            } else {
                this.filters['title'] = (motion: IMotion) => {
                    return motion.getTitle().toLowerCase().indexOf(this.searchTitle.toLowerCase()) !== -1;
                };
            }
            this.recalcMotionList();
        }

        public searchInitiatorChange($ev) {
            if ($ev.currentTarget.value === this.searchInitiator) {
                return;
            }
            this.searchInitiator = $ev.currentTarget.value;
            if (this.searchInitiator === '') {
                delete this.filters['initiator'];
            } else {
                this.filters['initiator'] = (motion: IMotion) => {
                    return motion.getInitiatorName().toLowerCase().indexOf(this.searchInitiator.toLowerCase()) !== -1;
                };
            }
            this.recalcMotionList();
        }

        public setStatusItem(selected) {
            if (parseInt(selected.id) === 0) {
                delete this.filters['status'];
            } else {
                this.filters['status'] = (motion: IMotion) => {
                    return motion.status == selected.id;
                };
            }
            this.recalcMotionList();
        }

        public setTagItem(selected) {
            if (parseInt(selected.id) === 0) {
                delete this.filters['tag'];
            } else {
                this.filters['tag'] = (motion: IMotion) => {
                    return motion.tags.filter(tag => tag.id === parseInt(selected.id)).length > 0;
                };
            }
            this.recalcMotionList();
        }
    }
</script>
