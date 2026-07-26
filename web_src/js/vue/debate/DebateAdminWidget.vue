<template>
    <div class="currentDebateAdminContent">
        <nav class="debateAdminTabs">
            <button v-for="tab in tabs" :key="tab" type="button" class="tab"
                    :class="{ active: activeTab === tab }" @click="activeTab = tab"
                    v-t="['debate', 'admin_tab_' + tab]"></button>
        </nav>

        <section v-if="activeTab === 'debated'" class="content debatedTab">
            <div v-if="!current" class="nothingDebated" v-t="['debate', 'nothing_debated']"></div>
            <div v-if="current" class="debatedItem">
                <div class="title">{{ current.title }}</div>
                <div class="proposer">
                    <span v-if="current.initiators_html" class="initiators">
                        <template v-t="['debate', 'submitted_by', false, {}, ': ']"></template>
                        <span v-html="current.initiators_html"></span>
                    </span>
                    <a v-if="current.url_html" :href="current.url_html" class="fulltextLink">
                        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                        <template v-t="['debate', 'fulltext']"></template>
                    </a>
                </div>
                <button type="button" class="btn btn-default btn-xs stopDebateBtn" :disabled="starting || stopping"
                        @click="stopDebate()">
                    <span class="glyphicon glyphicon-stop" aria-hidden="true"></span>
                    <template v-t="['debate', 'admin_stop_do']"></template>
                </button>
            </div>

            <div v-if="loadError" class="alert alert-danger">{{ loadError }}</div>
            <div v-if="startError" class="alert alert-danger">{{ startError }}</div>
            <template v-if="selectables">
                <div class="startDebateTitle" v-t="['debate', 'admin_start_debate', false, {}, ':']"></div>
                <div v-for="group in selectableGroups" :key="group.id" class="selectRow" :class="'selectRow-' + group.id">
                    <label class="rowLabel" :for="'debateAdminSelect-' + group.id"
                           v-t="['debate', 'admin_select_' + group.id, false, {}, ':']"></label>
                    <div class="rowSelect">
                        <select :id="'debateAdminSelect-' + group.id" class="stdDropdown" v-model="selected[group.id]">
                            <option v-for="item in group.items" :key="item.target_id" :value="item.target_id">
                                {{ item.title_with_prefix || item.title }}
                            </option>
                        </select>
                    </div>
                    <div class="rowButton">
                        <button type="button" class="btn btn-default" :disabled="selected[group.id] === null || starting || stopping"
                                @click="startDebate(group.id)" v-t="['debate', 'admin_select_do']"></button>
                    </div>
                </div>
            </template>
        </section>

        <section v-if="activeTab === 'speech'" class="content speechTab">
            <div v-if="!current" class="nothingDebated" v-t="['debate', 'nothing_debated']"></div>
            <template v-if="current">
                <div class="debatedItem">
                    <div class="title">{{ current.title }}</div>
                    <div class="proposer">
                        <span v-if="current.initiators_html" class="initiators">
                            <template v-t="['debate', 'submitted_by', false, {}, ': ']"></template>
                            <span v-html="current.initiators_html"></span>
                        </span>
                        <a v-if="current.url_html" :href="current.url_html" class="fulltextLink">
                            <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                            <template v-t="['debate', 'fulltext']"></template>
                        </a>
                    </div>
                </div>

                <div v-if="current.target_type === 'amendment'" class="alert alert-info"
                     v-t="['debate', 'admin_speech_no_amendment']"></div>
                <template v-else>
                    <div v-if="speechError" class="alert alert-danger">{{ speechError }}</div>
                    <div v-if="speechLoading && !speechQueue" class="speechLoading"
                         v-t="['debate', 'admin_speech_loading']"></div>
                    <speech-admin-widget v-if="speechQueue" :key="speechQueue.id"
                        :init-queue="speechQueue"
                        :csrf="csrf"
                        :component-admin-link="speechComponentAdminLink"
                        :poll-url="speechPollUrl"
                        :item-perform-operation-url="speechItemPerformOperationUrl"
                        :randomize-queue-url="speechRandomizeQueueUrl"
                        :reset-queue-url="speechResetQueueUrl"
                        :create-item-url="speechCreateItemUrl"
                        :set-status-url="speechSetStatusUrl"
                    ></speech-admin-widget>
                </template>
            </template>
        </section>

        <section v-if="activeTab === 'voting' || activeTab === 'protocol'" class="content underConstruction"
                 v-t="['debate', 'admin_under_construction']"></section>
    </div>
</template>

<script>
import { authorizedFetch, postJson, putJson, deleteJson } from "/js/modules/shared/ApiClient.js";
import Translate from "/js/vue/Translate.vue.js";

export default {
    name: 'DebateAdminWidget',
    props: {
        initState: {
            type: Object,
            required: true,
        },
        debateUrl: {
            type: String,
            required: true,
        },
        selectableUrl: {
            type: String,
            required: true,
        },
        speechQueueUrl: {
            type: String,
            required: true,
        },
        csrf: {
            type: String,
            required: true,
        },
        speechComponentAdminLink: {
            type: String,
            required: true,
        },
        speechPollUrl: {
            type: String,
            required: true,
        },
        speechItemPerformOperationUrl: {
            type: String,
            required: true,
        },
        speechRandomizeQueueUrl: {
            type: String,
            required: true,
        },
        speechResetQueueUrl: {
            type: String,
            required: true,
        },
        speechCreateItemUrl: {
            type: String,
            required: true,
        },
        speechSetStatusUrl: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            activeTab: 'debated',
            tabs: ['debated', 'speech', 'voting', 'protocol'],
            state: this.initState,
            selectables: null,
            selected: {
                motion: null,
                amendment: null,
                agenda_item: null,
            },
            starting: false,
            stopping: false,
            loadError: null,
            startError: null,
            speechQueue: null,
            speechLoading: false,
            speechError: null,
        };
    },
    computed: {
        current() {
            return this.state ? this.state.current : null;
        },
        currentDebateId() {
            return this.current ? this.current.id : null;
        },
        selectableGroups() {
            return [
                { id: 'motion', items: this.selectables.motions },
                { id: 'amendment', items: this.selectables.amendments },
                { id: 'agenda_item', items: this.selectables.agenda_items },
            ].filter(group => group.items.length > 0);
        },
    },
    watch: {
        activeTab() {
            this.maybeLoadSpeechQueue();
        },
        currentDebateId() {
            // A different item is being debated now: drop the old queue so the tab reloads the right one.
            this.speechQueue = null;
            this.speechError = null;
            this.maybeLoadSpeechQueue();
        },
    },
    methods: {
        maybeLoadSpeechQueue() {
            if (this.activeTab !== 'speech' || !this.current || this.current.target_type === 'amendment') {
                return;
            }
            if (this.speechQueue || this.speechLoading) {
                return;
            }
            this.loadSpeechQueue();
        },
        loadSpeechQueue() {
            this.speechLoading = true;
            this.speechError = null;
            postJson(this.speechQueueUrl, {})
                .then(queue => {
                    this.speechQueue = queue;
                })
                .catch(err => {
                    console.error('Could not load the speech queue for the debate', err);
                    this.speechError = Translate.getTranslation('debate', 'admin_speech_err');
                })
                .finally(() => {
                    this.speechLoading = false;
                });
        },
        loadSelectables() {
            authorizedFetch(this.selectableUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(selectables => {
                    this.selectables = selectables;
                    this.loadError = null;
                })
                .catch(err => {
                    console.error('Could not load the selectable debate items from the backend', err);
                    this.loadError = Translate.getTranslation('debate', 'admin_selectables_err');
                });
        },
        startDebate(targetType) {
            if (this.selected[targetType] === null || this.starting) {
                return;
            }
            this.starting = true;
            putJson(this.debateUrl, {
                target_type: targetType,
                target_id: this.selected[targetType],
            })
                .then(state => {
                    this.state = state;
                    this.startError = null;
                    this.selected = {
                        motion: null,
                        amendment: null,
                        agenda_item: null,
                    };
                })
                .catch(err => {
                    console.error('Could not start the debate', err);
                    this.startError = Translate.getTranslation('debate', 'admin_start_err');
                })
                .finally(() => {
                    this.starting = false;
                });
        },
        stopDebate() {
            if (this.stopping) {
                return;
            }
            this.stopping = true;
            deleteJson(this.debateUrl)
                .then(state => {
                    this.state = state;
                    this.startError = null;
                })
                .catch(err => {
                    console.error('Could not end the debate', err);
                    this.startError = Translate.getTranslation('debate', 'admin_stop_err');
                })
                .finally(() => {
                    this.stopping = false;
                });
        },
    },
    mounted() {
        this.loadSelectables();
    },
};
</script>
