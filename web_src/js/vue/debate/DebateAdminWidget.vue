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
                <div class="selectRow selectRow-free_text">
                    <label class="rowLabel" for="debateAdminFreeText"
                           v-t="['debate', 'admin_select_free_text', false, {}, ':']"></label>
                    <div class="rowSelect">
                        <input type="text" id="debateAdminFreeText" class="form-control" v-model="freeText">
                    </div>
                    <div class="rowButton">
                        <button type="button" class="btn btn-default" :disabled="!freeText || starting || stopping"
                                @click="startFreeTextDebate()" v-t="['debate', 'admin_select_do']"></button>
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
        </section>

        <section v-if="activeTab === 'voting'" class="content votingTab">
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

                <div v-if="votingError" class="alert alert-danger">{{ votingError }}</div>
                <div v-if="votingLoading && !votingState" class="votingLoading"
                     v-t="['debate', 'admin_voting_loading']"></div>

                <template v-if="votingState">
                    <!-- A voting is resolved/assigned: compact card + link to the full voting administration -->
                    <div v-if="votingState.resolved_voting_block" class="votingCard">
                        <div class="votingCardHead">
                            <span class="votingCardTitle">{{ votingState.resolved_voting_block.title }}</span>
                            <span class="votingCardStatus label label-default">{{ votingStatusLabel(votingState.resolved_voting_block.status) }}</span>
                        </div>
                        <div class="votingCardCounts" v-t="['debate', 'admin_voting_votes', false, {
                            '%TOTAL%': votingState.resolved_voting_block.votes_total,
                            '%USERS%': votingState.resolved_voting_block.votes_users
                        }]"></div>
                        <div class="votingCardActions">
                            <a :href="votingState.resolved_voting_block.admin_link" target="_blank" rel="noopener"
                               class="btn btn-default btn-xs" v-t="['debate', 'admin_voting_manage']"></a>
                            <button v-if="votingState.assigned_voting_block_id" type="button" class="btn btn-link btn-xs"
                                    :disabled="votingBusy" @click="unassignVoting()"
                                    v-t="['debate', 'admin_voting_unassign']"></button>
                        </div>
                        <div class="votingAssignRow">
                            <label class="rowLabel" for="debateVotingSelectOther"
                                   v-t="['debate', 'admin_voting_select_other', false, {}, ':']"></label>
                            <select id="debateVotingSelectOther" class="stdDropdown" v-model="selectedVotingBlockId">
                                <option :value="null" v-t="['debate', 'admin_voting_select_placeholder']"></option>
                                <option v-for="opt in assignableOptions" :key="opt.id" :value="opt.id">{{ opt.title }}</option>
                            </select>
                            <button type="button" class="btn btn-default btn-xs"
                                    :disabled="selectedVotingBlockId === null || votingBusy"
                                    @click="assignVoting()" v-t="['debate', 'admin_voting_assign']"></button>
                        </div>
                    </div>

                    <!-- No voting yet: create one for the debated item, or assign an existing block -->
                    <div v-else class="votingCreate">
                        <template v-if="votingState.create_mode === 'question'">
                            <label class="rowLabel" for="debateVotingQuestion"
                                   v-t="['debate', 'admin_voting_question', false, {}, ':']"></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="debateVotingQuestion" v-model="votingQuestion">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button" :disabled="!votingQuestion || votingBusy"
                                            @click="createVoting()" v-t="['debate', 'admin_voting_create']"></button>
                                </span>
                            </div>
                        </template>
                        <button v-else type="button" class="btn btn-default" :disabled="votingBusy"
                                @click="createVoting()" v-t="['debate', 'admin_voting_create']"></button>

                        <div class="votingAssignRow">
                            <label class="rowLabel" for="debateVotingSelectExisting"
                                   v-t="['debate', 'admin_voting_select_existing', false, {}, ':']"></label>
                            <select id="debateVotingSelectExisting" class="stdDropdown" v-model="selectedVotingBlockId">
                                <option :value="null" v-t="['debate', 'admin_voting_select_placeholder']"></option>
                                <option v-for="opt in assignableOptions" :key="opt.id" :value="opt.id">{{ opt.title }}</option>
                            </select>
                            <button type="button" class="btn btn-default btn-xs"
                                    :disabled="selectedVotingBlockId === null || votingBusy"
                                    @click="assignVoting()" v-t="['debate', 'admin_voting_assign']"></button>
                        </div>
                    </div>
                </template>
            </template>
        </section>

        <section v-if="activeTab === 'protocol'" class="content underConstruction"
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
        votingUrl: {
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
            freeText: '',
            starting: false,
            stopping: false,
            loadError: null,
            startError: null,
            speechQueue: null,
            speechLoading: false,
            speechError: null,
            votingState: null,
            votingLoading: false,
            votingError: null,
            votingBusy: false,
            votingQuestion: '',
            selectedVotingBlockId: null,
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
        assignableOptions() {
            if (!this.votingState) {
                return [];
            }
            const resolvedId = this.votingState.resolved_voting_block ? this.votingState.resolved_voting_block.id : null;
            return this.votingState.selectable_voting_blocks.filter(opt => opt.id !== resolvedId);
        },
    },
    watch: {
        activeTab() {
            this.maybeLoadSpeechQueue();
            this.maybeLoadVotingState();
        },
        currentDebateId() {
            // A different item is being debated now: drop the old queue/voting so the tabs reload the right one.
            this.speechQueue = null;
            this.speechError = null;
            this.maybeLoadSpeechQueue();

            this.votingState = null;
            this.votingError = null;
            this.selectedVotingBlockId = null;
            this.votingQuestion = '';
            this.maybeLoadVotingState();
        },
    },
    methods: {
        maybeLoadSpeechQueue() {
            if (this.activeTab !== 'speech' || !this.current) {
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
        maybeLoadVotingState() {
            if (this.activeTab !== 'voting' || !this.current || this.votingLoading) {
                return;
            }
            this.loadVotingState();
        },
        loadVotingState() {
            this.votingLoading = true;
            authorizedFetch(this.votingUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(state => {
                    this.votingState = state;
                    this.votingError = null;
                })
                .catch(err => {
                    console.error('Could not load the voting state for the debate', err);
                    this.votingError = Translate.getTranslation('debate', 'admin_voting_err');
                })
                .finally(() => {
                    this.votingLoading = false;
                });
        },
        assignVoting() {
            if (this.selectedVotingBlockId === null || this.votingBusy) {
                return;
            }
            this.votingBusy = true;
            putJson(this.votingUrl, { voting_block_id: this.selectedVotingBlockId })
                .then(state => this.applyVotingState(state))
                .catch(err => {
                    console.error('Could not assign the voting', err);
                    this.votingError = Translate.getTranslation('debate', 'admin_voting_err');
                })
                .finally(() => {
                    this.votingBusy = false;
                });
        },
        unassignVoting() {
            if (this.votingBusy) {
                return;
            }
            this.votingBusy = true;
            deleteJson(this.votingUrl)
                .then(state => this.applyVotingState(state))
                .catch(err => {
                    console.error('Could not unassign the voting', err);
                    this.votingError = Translate.getTranslation('debate', 'admin_voting_err');
                })
                .finally(() => {
                    this.votingBusy = false;
                });
        },
        createVoting() {
            if (this.votingBusy) {
                return;
            }
            this.votingBusy = true;
            postJson(this.votingUrl, { question: this.votingQuestion || null })
                .then(state => this.applyVotingState(state))
                .catch(err => {
                    console.error('Could not create the voting', err);
                    this.votingError = Translate.getTranslation('debate', 'admin_voting_err');
                })
                .finally(() => {
                    this.votingBusy = false;
                });
        },
        applyVotingState(state) {
            this.votingState = state;
            this.votingError = null;
            this.selectedVotingBlockId = null;
            this.votingQuestion = '';
        },
        votingStatusLabel(status) {
            const keys = {
                0: 'admin_voting_status_offline',
                1: 'admin_voting_status_preparing',
                2: 'admin_voting_status_open',
                3: 'admin_voting_status_closed',
                4: 'admin_voting_status_closed',
            };
            return Translate.getTranslation('debate', keys[status] || 'admin_voting_status_preparing');
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
        startFreeTextDebate() {
            const text = (this.freeText || '').trim();
            if (text === '' || this.starting) {
                return;
            }
            this.starting = true;
            putJson(this.debateUrl, {
                target_type: 'free_text',
                text,
            })
                .then(state => {
                    this.state = state;
                    this.startError = null;
                    this.freeText = '';
                })
                .catch(err => {
                    console.error('Could not start the free-text debate', err);
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
