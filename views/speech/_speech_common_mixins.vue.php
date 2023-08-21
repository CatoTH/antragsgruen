<?php

use app\components\UrlHelper;

$pollUrl       = UrlHelper::createUrl(['/speech/get-queue', 'queueId' => 'QUEUEID']);
$registerUrl   = UrlHelper::createUrl(['/speech/register', 'queueId' => 'QUEUEID']);
$unregisterUrl = UrlHelper::createUrl(['/speech/unregister', 'queueId' => 'QUEUEID']);
?>
<script>
    const pollUrl = <?= json_encode($pollUrl) ?>;
    const registerUrl = <?= json_encode($registerUrl) ?>;
    const unregisterUrl = <?= json_encode($unregisterUrl) ?>;
    const msgPersonsWaiting1 = "" + <?= json_encode(Yii::t('speech', 'persons_waiting_1')) ?>;
    const msgPersonsWaitingX = "" + <?= json_encode(Yii::t('speech', 'persons_waiting_x')) ?>;

    const SPEECH_COMMON_MIXIN = {
        data() {
            return {
                queue: null,
                pollingId: null,
                liveConnected: false,
                timerId: null,
                timeOffset: 0, // milliseconds the browser is ahead of the server time
                remainingSpeakingTime: null
            }
        },
        watch: {
            initQueue: {
                handler(newVal) {
                    this.queue = newVal;
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
                    return msgPersonsWaiting1;
                } else {
                    return msgPersonsWaitingX.replace(/%NUM%/, subqueue.num_applied);
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
                $.post(registerUrl.replace(/QUEUEID/, widget.queue.id), {
                    subqueue: subqueue.id,
                    username: this.registerName,
                    pointOfOrder: (pointOfOrder ? '1' : '0'),
                    _csrf: this.csrf,
                }, function (data) {
                    widget.queue = data;
                    widget.showApplicationForm = widget.defaultApplicationForm;
                }).catch(function (err) {
                    alert(err.responseText);
                });
            },
            onShowApplicationForm: function ($event, subqueue, pointOfOrder) {
                $event.preventDefault();

                this.showApplicationForm = subqueue.id;
                if (pointOfOrder) {
                    this.showApplicationForm += '_poo';
                }
                this.$nextTick(function () {
                    if (this.$refs.adderNameInputs) {
                        this.$refs.adderNameInputs[0].focus();
                    } else if (this.$refs.adderNameInput) {
                        this.$refs.adderNameInput.focus();
                    }
                });
            },
            removeMeFromQueue: function ($event) {
                $event.preventDefault();

                const widget = this;
                $.post(unregisterUrl.replace(/QUEUEID/, widget.queue.id), {
                    _csrf: this.csrf,
                }, function (data) {
                    widget.queue = data;
                }).catch(function (err) {
                    alert(err.responseText);
                });
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
            reloadData: function () {
                const widget = this;
                if (!widget.queue) {
                    return;
                }
                if (widget.liveConnected) {
                    return;
                }

                $.get(
                    pollUrl.replace(/QUEUEID/, widget.queue.id),
                    this.setData.bind(this)
                ).catch(function(err) {
                    console.error("Could not load speech queue data from backend", err);
                });
            },
            startPolling: function (highfrequency) {
                this.recalcTimeOffset(new Date());
                const reloadTimer = (highfrequency ? 1000 : 3000);

                const widget = this;
                this.pollingId = window.setInterval(function () {
                    widget.reloadData();
                }, reloadTimer);

                this.timerId = window.setInterval(function () {
                    widget.recalcRemainingTime();
                }, 100);

                if (window['ANTRAGSGRUEN_LIVE_EVENTS'] !== undefined) {
                    window['ANTRAGSGRUEN_LIVE_EVENTS'].registerListener('user', 'speech', (connectionEvent, speechEvent) => {
                        if (connectionEvent !== null) {
                            widget.liveConnected = connectionEvent;
                        }
                        if (speechEvent !== null) {
                            this.setData(speechEvent);
                        }
                    });
                }
            },
            stopPolling: function () {
                window.clearInterval(this.pollingId);
                window.clearInterval(this.timerId);
            }
        }
    };
</script>
