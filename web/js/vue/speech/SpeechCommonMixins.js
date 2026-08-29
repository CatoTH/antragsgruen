// @ts-check

import translate from "/js/vue/Translate.vue.js";
import { postJson } from "/js/modules/shared/ApiClient.js";
import { registerListener } from "/js/modules/shared/LiveData.js";

let TEMPLATE_REGISTER_URL = null;
let TEMPLATE_UNREGISTER_URL = null;

// The URL to poll the speaking lists is provided centrally by LiveData; only the URLs of the actions
// a user can perform on a speaking list need to be set by the view.
export function setSpeechActionUrls(registerUrl, unregisterUrl) {
    TEMPLATE_REGISTER_URL = registerUrl;
    TEMPLATE_UNREGISTER_URL = unregisterUrl;
}

export function getSpeechCommonMixins() {
    return {
        data() {
            return {
                // Both are meant to be overwritten by the data() of the component using this mixin
                // (polling starts as soon as a speaking list is set, which happens before any hook):
                // a more frequent update rate than the default one, and loading the data from the
                // backend for components that are rendered without a speaking list to begin with.
                pollIntervalMs: null,
                initialFetch: false,

                liveDataHandle: null,
                queue: null,
                timerId: null,
                timeOffset: 0, // milliseconds the browser is ahead of the server time
                remainingSpeakingTime: null
            }
        },
        watch: {
            initQueue: {
                handler(newVal) {
                    this.queue = newVal;
                    this.startPolling();
                },
                immediate: true
            }
        },
        computed: {
            activeSpeaker: function () {
                if (!this.queue) {
                    return null; // Currently loading
                }
                const active = this.queue.slots.filter(function (slot) {
                    return slot.date_stopped === null && slot.date_started !== null;
                });
                return (active.length > 0 ? active[0] : null);
            },
            upcomingSpeakers: function () {
                return this.queue.slots.filter(function (slot) {
                    return slot.date_stopped === null && slot.date_started === null;
                });
            },
            loginWarning: function () {
                return this.queue.requires_login && !this.user.logged_in;
            },
            hasSpeakingTime: function () {
                return this.queue.speaking_time > 0;
            },
            formattedRemainingTime: function () {
                const minutes = Math.floor(this.remainingSpeakingTime / 60);
                let seconds = this.remainingSpeakingTime - minutes * 60;
                if (seconds < 10) {
                    seconds = "0" + seconds;
                }

                return minutes + ":" + seconds;
            }
        },
        methods: {
            isMe: function (slot) {
                return slot.userId === this.user.id;
            },
            numAppliedTitle: function (subqueue) {
                if (subqueue.num_applied === 1) {
                    return translate.getTranslation("speech", "persons_waiting_1");
                } else {
                    return translate.getTranslation("speech", "persons_waiting_x").replace(/%NUM%/, subqueue.num_applied);
                }
            },
            formatUsernameHtml: function (item) {
                let name = item.name;
                name = name.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");

                // Replaces patterns like [[Remote]] by labels.
                return name.replaceAll(/\[\[(.*)]]/g, "<span class=\"label label-info\">$1</span>");
            },
            register: function ($event, subqueue, pointOfOrder) {
                $event.preventDefault();

                const widget = this;
                postJson(TEMPLATE_REGISTER_URL.replace(/QUEUEID/, widget.queue.id), {
                    subqueue: subqueue.id,
                    username: this.registerName,
                    point_of_order: !!pointOfOrder,
                }).then(function (data) {
                    widget.applyQueueUpdate(data);
                    widget.showApplicationForm = widget.defaultApplicationForm;
                }).catch(function (err) {
                    alert(err.message);
                });
            },
            onShowApplicationForm: function ($event, subqueue, pointOfOrder) {
                $event.preventDefault();

                this.showApplicationForm = subqueue.id;
                if (pointOfOrder) {
                    this.showApplicationForm += '_poo';
                }
                this.$nextTick(function () {
                    if (this.$refs.adderNameInput instanceof HTMLInputElement) {
                        // Single Queue
                        this.$refs.adderNameInput.focus();
                    } else {
                        // Multiple Queues
                        this.$refs.adderNameInput[0].focus();
                    }
                });
            },
            removeMeFromQueue: function ($event) {
                $event.preventDefault();

                const widget = this;
                postJson(TEMPLATE_UNREGISTER_URL.replace(/QUEUEID/, widget.queue.id), {})
                    .then(function (data) {
                        widget.applyQueueUpdate(data);
                    }).catch(function (err) {
                        alert(err.message);
                    });
            },
            /**
             * The answer to a change already is the new state of the speaking list, so it is published
             * on the channel rather than applied here: that way this widget applies it exactly like a
             * polled or pushed update, the other widgets showing the same speaking list get it right
             * away - which they would not, if this consultation has no live events - and updates that
             * were still running when the change was made can no longer set anybody back.
             */
            applyQueueUpdate: function (queue) {
                if (this.liveDataHandle) {
                    this.liveDataHandle.publishChange(queue);
                } else {
                    this.setData(queue);
                }
            },
            recalcTimeOffset: function (serverTime) {
                const browserTime = (new Date()).getTime();
                this.timeOffset = browserTime - serverTime.getTime();
            },
            recalcRemainingTime: function () {
                const active = this.activeSpeaker;
                if (!active) {
                    this.remainingSpeakingTime = null;
                    return;
                }
                const startedTs = (new Date(active.date_started)).getTime();
                const currentTs = (new Date()).getTime() - this.timeOffset;
                const secondsPassed = Math.round((currentTs - startedTs) / 1000);

                this.remainingSpeakingTime = this.queue.speaking_time - secondsPassed;
            },
            setData: function (data) {
                this.queue = data;
                this.recalcTimeOffset(new Date(data['current_time']));
                this.recalcRemainingTime();
            },
            startPolling: function () {
                if (!this.queue) {
                    console.log("No queue set");
                    return;
                }
                if (this.liveDataHandle) {
                    this.liveDataHandle.setKey(this.queue.id);
                    return;
                }

                this.liveDataHandle = registerListener('user', 'speech', {
                    key: this.queue.id,
                    intervalMs: this.pollIntervalMs,
                    initialFetch: this.initialFetch,
                    onData: (queue) => this.setData(queue),
                });

                // The remaining speaking time is counted down locally, independently of the updates
                this.timerId = window.setInterval(() => this.recalcRemainingTime(), 100);
            },
            stopPolling: function () {
                if (this.liveDataHandle) {
                    this.liveDataHandle.unregister();
                    this.liveDataHandle = null;
                }
                if (this.timerId) {
                    window.clearInterval(this.timerId);
                    this.timerId = null;
                }
            }
        }
    }
}
