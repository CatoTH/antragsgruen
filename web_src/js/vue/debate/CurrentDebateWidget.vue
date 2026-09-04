<template>
    <div class="currentDebateContent" :class="layoutClass">
        <div class="content debateTitleHolder">
            <div v-if="loaded && !current" class="nothingDebated" v-t="['debate', 'nothing_debated']"></div>
            <div v-if="current" class="debatedItem">
                <div class="title">{{ current.title }}</div>
                <div class="proposer">
                    <span v-if="current.initiators_html" class="initiators">
                        <template v-t="['debate', 'submitted_by', false, {}, ': ']"></template>
                        <span v-html="current.initiators_html"></span>
                    </span>
                    <a v-if="current.url_html && !projector" :href="current.url_html" class="fulltextLink">
                        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                        <template v-t="['debate', 'fulltext']"></template>
                    </a>
                </div>
            </div>
        </div>

        <div v-if="current && hasSpeechSection" class="debateSpeechHolder">
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
        </div>

        <div v-if="current && hasVotingSection" class="debateVotingHolder">
            <div v-if="votingError" class="alert alert-danger">{{ votingError }}</div>
            <div v-if="votingBlock" class="votingCommon currentDebateVoting">
                <voting-block-widget :key="votingBlock.id" :voting="votingBlock"
                                     :admin-link="votingAdminLink" :projector="projector"
                                     @vote="vote" @abstain="abstain"></voting-block-widget>
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
import Translate from "/js/vue/Translate.vue.js";
import { registerListener } from "/js/modules/shared/LiveData.js";
import { authorizedFetch } from "/js/modules/shared/ApiClient.js";


export default {
    name: 'CurrentDebateWidget',
    props: {
        initState: {
            // Optional: the fullscreen projector passes no state (it would be stale by the time it is
            // opened) and the widget loads the current state from the backend on mount instead.
            type: Object,
            default: null,
        },
        csrf: {
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
            state: this.initState || null,
            // Whether a debate state has been obtained yet (from initState, or the first backend load).
            // The projector starts without one, so nothing is shown until the load completes rather than
            // briefly flashing "nothing debated".
            loaded: !!this.initState,
            debateHandle: null,
            speechHandle: null,
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
            votings: [],
            votingHandle: null,
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
          return this.current && this.current.speech_queue ? this.current.speech_queue.id : null;
        },
        currentVotingBlockId() {
          return this.current && this.current.voting_block ? this.current.voting_block.id : null;
        },
        hasSpeechSection() {
          // An inactive speaking list renders nothing at all (see .disabledSpeechQueue), so it does
          // not count as a section of its own.
          return !!(this.speechError || this.speechLoading || (this.speechQueue && this.speechQueue.is_active));
        },
        hasVotingSection() {
          return !!(this.votingError || this.votingBlock);
        },
        layoutClass() {
          // On the projector the available height is distributed between the debated item's title,
          // the speaking list and the voting; the number of visible sections decides the proportions
          // (see _projector.scss).
          if (!this.projector) {
            return null;
          }
          const sections = 1 + (this.hasSpeechSection ? 1 : 0) + (this.hasVotingSection ? 1 : 0);
          return 'debateSections' + sections;
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
        currentSpeechQueueId(newVal) {
            // A different item is being debated now: drop the old queue so the tab reloads the right one.
            this.speechQueue = null;
            this.speechError = null;
            this.speechLoading = (newVal !== null);
            if (this.speechHandle) {
                this.speechHandle.setKey(newVal);
            }
        },
        currentVotingBlockId() {
            // The debated item (or its assigned voting) changed: reload the matching voting block.
            this.votingBlock = null;
            this.votingError = null;
            this.pickVotingOfDebate();
        },
    },
    methods: {
        setSpeechQueue(queue) {
            this.speechQueue = queue;
            this.speechLoading = false;
            this.speechError = null;
        },
        /**
         * The voting channel carries every voting that is open; of those, this widget shows the one
         * the debated item is being voted on with - and nothing once that voting is closed, just
         * like the standalone widget on the home page.
         */
        onVotings(votings) {
            this.votings = votings;
            this.votingLoading = false;
            this.pickVotingOfDebate();
        },
        pickVotingOfDebate() {
            const votingBlockId = this.current && this.current.voting_block ? this.current.voting_block.id : null;
            if (!votingBlockId) {
                this.votingBlock = null;
                return;
            }

            // Only while it is open: the channel also carries events about votings in other states,
            // which say no more than their ID and their status (VotingPayloadBuilder) and are there
            // for a reader to drop them.
            this.votingBlock = this.votings.find(
                voting => parseInt(voting.id) === parseInt(votingBlockId) && voting.status === 'open'
            ) || null;
        },
        vote(votingBlockId, groupId, vote) {
            this._votePost(votingBlockId, {
                votes: [{groupId, vote}],
            });
        },
        abstain(votingBlockId, setAbstention) {
            this._votePost(votingBlockId, {
                abstention: {abstain: setAbstention},
            });
        },
        _votePost(votingBlockId, postData) {
            authorizedFetch(this.votingVoteUrl.replace(/VOTINGBLOCKID/, votingBlockId), {
                method: 'POST',
                headers: {'Content-Type': 'application/json; charset=utf-8'},
                body: JSON.stringify(postData),
            })
                .then(response => response.json())
                .then(data => {
                    if (data && data.success !== undefined && !data.success) {
                        alert(data.message);
                        return;
                    }
                    if (Array.isArray(data)) {
                        this.onVotings(data);
                    }
                    if (this.votingHandle) {
                        // So that the widgets of other pages and tabs see the vote as well
                        this.votingHandle.refreshNow();
                    }
                })
                .catch(err => {
                    console.error('Could not submit the vote', err);
                });
        },
        setDebateState(state) {
          this.state = state;
          this.loaded = true;
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
                                        this.debateHandle.refreshNow();
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
        this.debateHandle = registerListener('user', 'debate', {
            // Without an initial state - as on the projector - nothing can be shown until the state
            // was loaded from the backend.
            initialFetch: !this.initState,
            onData: (state) => this.setDebateState(state),
        });

        // The speaking list of the debated item is loaded from the backend in any case: neither the
        // initial state nor the live events of the debate channel contain it.
        this.speechLoading = (this.currentSpeechQueueId !== null);
        this.speechHandle = registerListener('user', 'speech', {
            key: this.currentSpeechQueueId,
            initialFetch: true,
            onData: (queue) => this.setSpeechQueue(queue),
            onError: () => {
                this.speechLoading = false;
                this.speechError = Translate.getTranslation('debate', 'admin_speech_err');
            },
        });

        // Adding & seconding secondary motions is disabled for now:
        // this.loadMotionTypes();

        // Votings are not pushed via Live yet, so the widget keeps its own polling cycle.
        // Anonymous visitors have no access to the votings, and the channel is not declared for them
        if (this.votingPollUrl) {
            this.votingLoading = true;
            this.votingHandle = registerListener('user', 'voting', {
                onData: votings => this.onVotings(votings),
                onError: () => {
                    this.votingLoading = false;
                    this.votingError = Translate.getTranslation('debate', 'voting_err');
                },
                initialFetch: true,
            });
        }
    },
    beforeUnmount() {
        if (this.debateHandle) {
            this.debateHandle.unregister();
            this.debateHandle = null;
        }
        if (this.speechHandle) {
            this.speechHandle.unregister();
            this.speechHandle = null;
        }
        if (this.votingHandle) {
            this.votingHandle.unregister();
        }
    },
};
</script>
