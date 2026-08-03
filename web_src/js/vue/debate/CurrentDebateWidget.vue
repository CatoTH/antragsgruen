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

                <div v-if="current.target_type === 'amendment'" class="alert alert-info"
                     v-t="['debate', 'admin_speech_no_amendment']"></div>
                <template v-else>
                  <div v-if="speechError" class="alert alert-danger">{{ speechError }}</div>
                  <div v-if="speechLoading && !speechQueue" class="speechLoading"
                       v-t="['debate', 'admin_speech_loading']"></div>
                  <speech-user-inline-widget v-if="speechQueue" :key="speechQueue.id"
                                             :init-queue="speechQueue"
                                             :csrf="csrf"
                                             :user="speechUser"
                                             :title="'title'"
                  ></speech-user-inline-widget>
                </template>
            </div>
        </div>
        <footer class="content secondaryMotionRow">
            <!-- Placeholder: secondary motions raised from the audience will be listed and managed here -->
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
        currentUser: {
            type: Object,
            default: null,
        },
    },
    data() {
        return {
            state: this.initState,
            pollingId: null,
            motionTypes: null,
            raiseFormMotionType: null,
            raiseFormHolder: null,
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
        creatableMotionTypes() {
            return (this.motionTypes || []).filter(
                motionType => motionType.policies.motions.current_user_permitted && !motionType.settings.amendments_only
            );
        },
    },
    watch: {
        currentDebateId() {
            // A different item is being debated now: drop the old queue so the tab reloads the right one.
            this.speechQueue = null;
            this.speechError = null;
            this.maybeLoadSpeechQueue();
        },
    },
    methods: {
        maybeLoadSpeechQueue() {
            if (!this.current || this.current.target_type === 'amendment' || !this.current.speech_queue_id) {
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
        reloadData() {
            fetch(this.pollUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP status ' + response.status);
                    }
                    return response.json();
                })
                .then(state => {
                    this.state = state;
                })
                .catch(err => {
                    console.error('Could not load the debate state from the backend', err);
                });
        },
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
    },
    mounted() {
        this.pollingId = window.setInterval(() => this.reloadData(), POLLING_INTERVAL);
        this.loadMotionTypes();
        this.maybeLoadSpeechQueue();

        if (window['ANTRAGSGRUEN_LIVE_EVENTS'] !== undefined) {
          // @TODO Proper integration
          window['ANTRAGSGRUEN_LIVE_EVENTS'].registerListener('user', 'debate', (connectionEvent, debateEvent) => {
            if (connectionEvent !== null) {
              //widget.liveConnected = connectionEvent;
            }
            console.log(debateEvent);
            if (debateEvent !== null) {
              //this.setData([speechEvent]);
            }
          });
        }
    },
    beforeUnmount() {
        window.clearInterval(this.pollingId);
    },
};
</script>
