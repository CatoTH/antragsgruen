<template>
    <div class="currentDebateContent">
        <div class="content">
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

                <div v-if="speechError" class="alert alert-danger">{{ speechError }}</div>
                <div v-if="speechLoading && !speechQueue" class="speechLoading"
                     v-t="['debate', 'admin_speech_loading']"></div>
                <!-- On the projector the read-only fullscreen speech display is used; on the regular
                     homepage the interactive inline widget (apply / withdraw) is shown instead. -->
                <fullscreen-speech v-if="speechQueue && projector" :key="'fs-' + speechQueue.id"
                                   :init-queue="speechQueue"
                                   :csrf="null"
                                   :user="null"
                                   :title="'title'"
                ></fullscreen-speech>
                <speech-user-inline-widget v-if="speechQueue && !projector" :key="speechQueue.id"
                                           :init-queue="speechQueue"
                                           :csrf="csrf"
                                           :user="speechUser"
                                           :title="'title'"
                ></speech-user-inline-widget>

                <div v-if="votingError" class="alert alert-danger">{{ votingError }}</div>
                <div v-if="votingBlock" class="votingCommon currentDebateVoting">
                    <voting-block-widget :key="votingBlock.id" :voting="votingBlock"
                                         :admin-link="votingAdminLink" :projector="projector"
                                         @vote="vote" @abstain="abstain"></voting-block-widget>
                </div>
            </div>
        </div>
        <!-- Adding & seconding secondary motions is disabled for now.
        <footer class="content secondaryMotionRow">
            <div class="raisedSecondaryMotions">
                <strong v-t="['debate', 'secondary_raised', false, {}, ':']"></strong><br>
                <span class="noSecondaryMotion" v-t="['debate', 'secondary_none']"></span>
            </div>
            <div v-if="creatableMotionTypes.length > 0" class="raiseSecondaryMotion">
                <button v-for="motionType in creatableMotionTypes" :key="motionType.id" type="button"
                        class="btn btn-xs btn-default" @click="openRaiseForm(motionType)">
                    {{ motionType.labels.create }}
                </button>
            </div>
        </footer>
        <teleport v-if="raiseFormMotionType && raiseFormHolder" :to="raiseFormHolder">
            <raise-secondary-motion-form ref="raiseForm" :motion-type="raiseFormMotionType"
                                         :create-url="createMotionUrl" :current-user="currentUser"></raise-secondary-motion-form>
        </teleport>
        -->
    </div>
</template>

<script>
import {authorizedFetch, getJson, postJson} from "/js/modules/shared/ApiClient.js";
import Translate from "/js/vue/Translate.vue.js";

const POLLING_INTERVAL = 3000;

export default {
    name: 'CurrentDebateWidget',
    props: {
        initState: {
            type: Object,
            required: true,
        },
        csrf: {
            type: String,
            required: true,
        },
        pollUrl: {
            type: String,
            required: true,
        },
        motionTypesUrl: {
            type: String,
            required: true,
        },
        createMotionUrl: {
            type: String,
            required: true,
        },
        speechPollUrl: {
            type: String,
            required: true,
        },
        speechUser: {
            type: Object,
            required: true,
        },
        votingPollUrl: {
            // Empty for anonymous visitors: the voting endpoints require a logged-in user
            type: String,
            default: '',
        },
        votingVoteUrl: {
            type: String,
            default: '',
        },
        votingAdminLink: {
            type: String,
            default: '',
        },
        currentUser: {
            type: Object,
            default: null,
        },
        projector: {
            // Read-only projector mode: renders the read-only fullscreen speech display and hides the
            // interactive voting controls (used by the fullscreen projector, see FullscreenPanel.vue).
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            state: this.initState,
            pollingId: null,
            // Adding & seconding secondary motions is disabled for now:
            // motionTypes: null,
            // raiseFormMotionType: null,
            // raiseFormHolder: null,
            speechQueue: null,
            speechLoading: false,
            speechError: null,
            votingBlock: null,
            votingLoading: false,
            votingError: null,
            votingPollingId: null,
        };
    },
    computed: {
        current() {
            return this.state ? this.state.current : null;
        },
        currentDebateId() {
          return this.current ? this.current.id : null;
        },
        currentSpeechQueueId() {
          return this.current ? this.current.speech_queue_id : null;
        },
        currentVotingBlockId() {
          return this.current ? this.current.voting_block_id : null;
        },
        /* Adding & seconding secondary motions is disabled for now:
        creatableMotionTypes() {
            return (this.motionTypes || []).filter(
                motionType => motionType.policies.motions.current_user_permitted && !motionType.settings.amendments_only
            );
        },
        */
    },
    watch: {
        currentSpeechQueueId() {
            // A different item is being debated now: drop the old queue so the tab reloads the right one.
            this.speechQueue = null;
            this.speechError = null;
            this.maybeLoadSpeechQueue();
        },
        currentVotingBlockId() {
            // The debated item (or its assigned voting) changed: reload the matching voting block.
            this.votingBlock = null;
            this.votingError = null;
            this.refreshVoting(true);
        },
    },
    methods: {
        maybeLoadSpeechQueue() {
            if (!this.current || !this.current.speech_queue_id) {
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
            getJson(this.speechPollUrl.replace(/QUEUEIDS/, this.current.speech_queue_id))
                .then(queues => {
                    queues.forEach(queue => {
                      if (queue.id === this.current.speech_queue_id) {
                        this.speechQueue = queue;
                      }
                    })
                })
                .catch(err => {
                    console.error('Could not load the speech queue for the debate', err);
                    this.speechError = Translate.getTranslation('debate', 'admin_speech_err');
                })
                .finally(() => {
                    this.speechLoading = false;
                });
        },
        refreshVoting(initial) {
            // The voting widget keeps using the session-based /voting endpoints (they require a
            // logged-in user); anonymous visitors get an empty votingPollUrl and no voting is shown.
            if (!this.votingPollUrl) {
                return;
            }
            const votingBlockId = this.current ? this.current.voting_block_id : null;
            if (!votingBlockId) {
                this.votingBlock = null;
                return;
            }
            if (initial) {
                this.votingLoading = true;
                this.votingError = null;
            }
            fetch(this.votingPollUrl, {headers: {'Accept': 'application/json'}})
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(votings => {
                    // get-open-voting-blocks only returns open blocks, so the widget disappears once
                    // the vote is closed - identical to the standalone homepage voting widget.
                    this.votingBlock = votings.find(voting => voting.id === votingBlockId) || null;
                })
                .catch(err => {
                    console.error('Could not load the voting for the debate', err);
                    if (initial) {
                        this.votingError = Translate.getTranslation('debate', 'voting_err');
                    }
                })
                .finally(() => {
                    this.votingLoading = false;
                });
        },
        vote(votingBlockId, itemGroupSameVote, itemType, itemId, vote, votePublic) {
            this._votePost(votingBlockId, {
                votes: [{itemGroupSameVote, itemType, itemId, vote, "public": votePublic}],
            });
        },
        abstain(votingBlockId, setAbstention, votePublic) {
            this._votePost(votingBlockId, {
                abstention: {abstain: setAbstention, "public": votePublic},
            });
        },
        _votePost(votingBlockId, postData) {
            fetch(this.votingVoteUrl.replace(/VOTINGBLOCKID/, votingBlockId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'X-CSRF-Token': this.csrf,
                },
                body: JSON.stringify(postData),
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success !== undefined && !data.success) {
                        alert(data.message);
                        return;
                    }
                    this.votingBlock = (Array.isArray(data) ? data.find(voting => voting.id === votingBlockId) : null) || null;
                })
                .catch(err => {
                    console.error('Could not submit the vote', err);
                });
        },
        setDebateState(state) {
          this.state = state;
        },
        reloadData() {
            if (this.liveConnected) {
              return;
            }
            fetch(this.pollUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(state => {
                    this.setDebateState(state);
                })
                .catch(err => {
                    console.error('Could not load the debate state from the backend', err);
                });
        },
        /* Adding & seconding secondary motions is disabled for now:
        loadMotionTypes() {
            authorizedFetch(this.motionTypesUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(list => {
                    this.motionTypes = list.items;
                })
                .catch(err => {
                    // Expected e.g. for anonymous users when the public API is disabled - no buttons are shown then
                    console.warn('Could not load the motion types from the backend', err);
                });
        },
        openRaiseForm(motionType) {
            // The actual form is a Vue component that gets teleported into the bootbox dialog body
            const holder = document.createElement('div');
            const dialog = bootbox.dialog({
                title: motionType.labels.create,
                message: holder,
                buttons: {
                    cancel: {
                        label: Translate.getTranslation('debate', 'secondary_form_cancel'),
                        className: 'btn-link',
                    },
                    submit: {
                        label: Translate.getTranslation('debate', 'secondary_form_submit'),
                        className: 'btn-primary',
                        callback: () => {
                            if (this.$refs.raiseForm) {
                                this.$refs.raiseForm.submit().then(created => {
                                    if (created) {
                                        dialog.modal('hide');
                                        bootbox.alert(Translate.getTranslation('debate', 'secondary_form_created'));
                                        this.reloadData();
                                    }
                                });
                            }
                            // Keep the dialog open; it is closed explicitly once the motion was created
                            return false;
                        },
                    },
                },
            });
            dialog.on('hidden.bs.modal', () => {
                this.raiseFormMotionType = null;
                this.raiseFormHolder = null;
            });

            this.raiseFormHolder = holder;
            this.raiseFormMotionType = motionType;
        },
        */
    },
    mounted() {
        this.pollingId = window.setInterval(() => this.reloadData(), POLLING_INTERVAL);
        // Adding & seconding secondary motions is disabled for now:
        // this.loadMotionTypes();
        this.maybeLoadSpeechQueue();

        // Votings are not pushed via Live yet, so the widget keeps its own polling cycle.
        this.refreshVoting(true);
        this.votingPollingId = window.setInterval(() => this.refreshVoting(false), POLLING_INTERVAL);

        if (window['ANTRAGSGRUEN_LIVE_EVENTS'] !== undefined) {
          window['ANTRAGSGRUEN_LIVE_EVENTS'].registerListener('user', 'debate', (connectionEvent, debateEvent) => {
            if (connectionEvent !== null) {
              this.liveConnected = connectionEvent;
            }
            if (debateEvent !== null) {
              this.setDebateState(debateEvent);
            }
          });
        }
    },
    beforeUnmount() {
        window.clearInterval(this.pollingId);
        window.clearInterval(this.votingPollingId);
    },
};
</script>
