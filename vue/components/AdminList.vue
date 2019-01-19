<template>
    <section>
        <section class="motionListSearchForm fuelux">
            <label class="filterPrefix">
                {{ translate('admin', 'filter_prefix') }}:<br>
                <input type="text" class="form-control inputPrefix" v-bind:value="searchPrefix"
                       @change="searchPrefixChange" @keyup="searchPrefixChange">
            </label>
            <label class="filterTitle">
                {{ translate('admin', 'filter_title') }}:<br>
                <input type="text" class="form-control inputPrefix" v-bind:value="searchTitle"
                       @change="searchTitleChange" @keyup="searchTitleChange">
            </label>
            <label class="filterStatus">
                {{ translate('admin', 'filter_status') }}:<br>
                <select-list :items="getAvailableStatusItems()" @selected="setStatusItem"/>
            </label>
            <label class="filterInitiator">
                {{ translate('admin', 'filter_initiator') }}:<br>
                <input type="text" class="form-control inputPrefix" v-bind:value="searchInitiator"
                       @change="searchInitiatorChange" @keyup="searchInitiatorChange">
            </label>
            <label v-if="hasTopics" class="filterTopics">
                {{ translate('admin', 'filter_tag') }}:<br>
                <select-list :items="getAvailableTagsItems()" @selected="setTagItem"/>
            </label>
        </section>
        <br style="clear:both;">
        <table class="adminMotionTable">
            <thead>
            <tr>
                <th></th>
                <th>{{ translate('admin', 'list_type') }}</th>
                <th>
                    <span v-if="sort === 'prefix'" class="sortSelected">
                        {{ translate('admin', 'list_prefix') }}
                    </span>
                    <button class="btn-link sortSelectable" v-if="sort !== 'prefix'" @click="setSort('prefix')">
                        {{ translate('admin', 'list_prefix') }}
                    </button>
                </th>
                <th>
                    <span v-if="sort === 'title'" class="sortSelected">
                        {{ translate('admin', 'list_title') }}
                    </span>
                    <button class="btn-link sortSelectable" v-if="sort !== 'title'" @click="setSort('title')">
                        {{ translate('admin', 'list_title') }}
                    </button>
                </th>
                <th>
                    <span v-if="sort === 'status'" class="sortSelected">
                        {{ translate('admin', 'list_status') }}
                    </span>
                    <button class="btn-link sortSelectable" v-if="sort !== 'status'" @click="setSort('status')">
                        {{ translate('admin', 'list_status') }}
                    </button>
                </th>
                <th>
                    <span v-if="sort === 'initiator'" class="sortSelected">
                        {{ translate('admin', 'list_initiators') }}
                    </span>
                    <button class="btn-link sortSelectable" v-if="sort !== 'initiator'" @click="setSort('initiator')">
                        {{ translate('admin', 'list_initiators') }}
                    </button>
                </th>
                <th v-if="hasTopics">
                    <span v-if="sort === 'topic'" class="sortSelected">
                        {{ translate('admin', 'list_tag') }}
                    </span>
                    <button class="btn-link sortSelectable" v-if="sort !== 'topic'" @click="setSort('topic')">
                        {{ translate('admin', 'list_tag') }}
                    </button>
                </th>
                <th>{{ translate('admin', 'list_export') }}</th>
                <th>{{ translate('admin', 'list_action') }}</th>
            </tr>
            </thead>
            <tbody>
            <template v-for="item in sortedFilteredItems()">
                <tr v-if="item.type === 'motion'" :key="item.getTrackId()">
                    <td><input type="checkbox"></td>
                    <td i18n="admin-index motion indicator">{{ translate('admin', 'list_motion_short') }}</td>
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
                           i18n="admin-index export">{{ translate('admin', 'list_pdf_amend') }}</a> /
                        <a v-bind:href="item.getLink('motion/odt', linkTemplatesArr)" class="odt"
                           i18n="admin-index export">ODT</a> /
                        <a v-bind:href="item.getLink('motion/plainhtml', linkTemplatesArr)" class="html"
                           i18n="admin-index export">HTML</a>
                    </td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                                    aria-expanded="false">
                                {{ translate('admin', 'list_action') }}
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li v-if="item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="motionScreen(item, $event)" class="screen"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_screen') }}</a>
                                </li>
                                <li v-if="!item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="motionUnscreen(item, $event)" class="unscreen"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_unscreen') }}</a>
                                </li>
                                <li>
                                    <a tabindex="-1" v-bind:href="item.getLink('motion/clone', linkTemplatesArr)"
                                       class="asTemplate" target="_blank"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_template_motion') }}</a>
                                </li>
                                <li>
                                    <a tabindex="-1" href="#" @click="motionDelete(item, $event)" class="delete"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_delete') }}</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
                <tr v-if="item.type === 'amendment'" :key="item.getTrackId()">
                    <td><input type="checkbox"></td>
                    <td i18n="admin-index amendment indicator">{{ translate('admin', 'list_amend_short') }}</td>
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
                                {{ translate('admin', 'list_action') }}
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li v-if="item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="amendmentScreen(item, $event)" class="screen"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_screen') }}</a>
                                </li>
                                <li v-if="!item.isScreenable()">
                                    <a tabindex="-1" href="#" @click="amendmentUnscreen(item, $event)" class="unscreen"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_unscreen') }}</a>
                                </li>
                                <li>
                                    <a tabindex="-1" v-bind:href="item.getLink('amendment/clone', linkTemplatesArr)"
                                       class="asTemplate" target="_blank"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_template_amendment') }}</a>
                                </li>
                                <li>
                                    <a tabindex="-1" href="#" @click="amendmentDelete(item, $event)" class="delete"
                                       i18n="admin-index action dropdown">{{ translate('admin', 'list_delete') }}</a>
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
    import axios from 'axios';
    import {merge} from 'rxjs';
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
        @Prop() wsUri!: string;
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

        public sort = "prefix";
        public searchPrefix = "";
        public searchTitle = "";
        public searchInitiator = "";
        private filters: { [filterId: string]: (IMotion) => boolean } = {};

        created() {
            const data = JSON.parse(this.initCollections);

            this.motionCollection.setElements(data['motions']);
            this.amendmentCollection.setElements(data['amendments']);

            merge(this.motionCollection.changed$, this.amendmentCollection.changed$).pipe(debounceTime(1)).subscribe(() => {
                this.recalcMotionList();
            });

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
            this._websocket.connect(this.cookie, this.wsUri);
        }

        get debugLog(): string {
            return this.log;
        }

        get hasTopics(): boolean {
            return Object.keys(this.motionCollection.elements).filter(motionId => {
                const motion: Motion = this.motionCollection.elements[motionId];
                return motion.tags.length > 0;
            }).length > 0;
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

            switch (this.sort) {
                case 'title':
                    this._sortedFilteredItems = AdminList.sortMotionsAmendmentsTitle(this._sortedFilteredItems);
                    break;
                case 'status':
                    this._sortedFilteredItems = AdminList.sortMotionsAmendmentsStatus(this._sortedFilteredItems);
                    break;
                case 'initiator':
                    this._sortedFilteredItems = AdminList.sortMotionsAmendmentsByInitiator(this._sortedFilteredItems);
                    break;
                case 'topic':
                    this._sortedFilteredItems = AdminList.sortMotionsAmendmentsByTopic(this._sortedFilteredItems);
                    break;
                case 'prefix':
                default:
                    this._sortedFilteredItems = AdminList.sortMotionsAmendmentsByPrefix(this._sortedFilteredItems);
            }

            this.$forceUpdate();
        }

        private static compareValues(val1, val2) {
            if (val1 < val2) {
                return -1;
            } else if (val1 > val2) {
                return 1;
            } else {
                return 0;
            }
        }

        private static sortMotionsAmendmentsStatus(items: IMotion[]): IMotion[] {
            return items.sort((motion1, motion2) => {
                if (motion1.status == motion2.status) {
                    return AdminList.compareValues(motion1.titlePrefix, motion2.titlePrefix);
                } else {
                    return AdminList.compareValues(motion1.status, motion2.status);
                }
            });
        }

        private static sortMotionsAmendmentsByTopic(items: IMotion[]): IMotion[] {
            return items.sort((motion1, motion2) => {
                return AdminList.compareValues(motion1.titlePrefix, motion2.titlePrefix);
                // @TODO
            });
        }

        private static sortMotionsAmendmentsByInitiator(items: IMotion[]): IMotion[] {
            return items.sort((motion1, motion2) => {
                if (motion1.getInitiatorName() == motion2.getInitiatorName()) {
                    return AdminList.compareValues(motion1.titlePrefix, motion2.titlePrefix);
                } else {
                    return AdminList.compareValues(motion1.getInitiatorName(), motion2.getInitiatorName());
                }
            });
        }

        private static sortMotionsAmendmentsTitle(items: IMotion[]): IMotion[] {
            return items.sort((motion1, motion2) => {
                if (motion1.getTitle() == motion2.getTitle()) {
                    return AdminList.compareValues(motion1.titlePrefix, motion2.titlePrefix);
                } else {
                    return AdminList.compareValues(motion1.getTitle(), motion2.getTitle());
                }
            });
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

        public translate(category: string, key: string): string {
            return Translations.get(category, key);
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

        public setSort(type: string): void {
            this.sort = type;
            this.recalcMotionList();
        }
    }
</script>
