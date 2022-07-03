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
            register: function ($event, subqueue) {
                $event.preventDefault();

                const widget = this;
                $.post(registerUrl.replace(/QUEUEID/, widget.queue.id), {
                    subqueue: subqueue.id,
                    username: this.registerName,
                    _csrf: this.csrf,
                }, function (data) {
                    widget.queue = data;
                    widget.showApplicationForm = false;
                }).catch(function (err) {
                    alert(err.responseText);
                });
            },
            onShowApplicationForm: function ($event, subqueue) {
                $event.preventDefault();

                this.showApplicationForm = subqueue.id;
                this.$nextTick(function () {
                    if (this.$refs.adderNameInputs) {
                        this.$refs.adderNameInputs[0].focus();
                    } else {
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
            reloadData: function () {
                const widget = this;
                if (!widget.queue) {
                    return;
                }

                $.get(pollUrl.replace(/QUEUEID/, widget.queue.id), function (data) {
                    widget.queue = data;
                    widget.recalcTimeOffset(new Date(data['current_time']));
                    widget.recalcRemainingTime();
                }).catch(function(err) {
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
            },
            stopPolling: function () {
                window.clearInterval(this.pollingId);
                window.clearInterval(this.timerId);
            }
        }
    };
</script>
