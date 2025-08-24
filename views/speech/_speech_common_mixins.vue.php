<?php

use app\components\UrlHelper;

$pollUrl       = UrlHelper::createUrl(['/speech/get-queue', 'queueIds' => 'QUEUEIDS']);
$registerUrl   = UrlHelper::createUrl(['/speech/register', 'queueId' => 'QUEUEID']);
$unregisterUrl = UrlHelper::createUrl(['/speech/unregister', 'queueId' => 'QUEUEID']);
?>
<script>
    const pollUrl = <?= json_encode($pollUrl) ?>;
    const registerUrl = <?= json_encode($registerUrl) ?>;
    const unregisterUrl = <?= json_encode($unregisterUrl) ?>;
    const msgPersonsWaiting1 = "" + <?= json_encode(Yii::t('speech', 'persons_waiting_1')) ?>;
    const msgPersonsWaitingX = "" + <?= json_encode(Yii::t('speech', 'persons_waiting_x')) ?>;

    const SPEECH_POLLER = new (function () {
        this.timeOffset = 0;
        this.liveConnected = null;

        this.listeners = [];

        this.recalcTimeOffset = function(serverTime) {
            const browserTime = (new Date()).getTime();
            this.timeOffset = browserTime - serverTime.getTime();
        };

        this.registerListener = function(queueId, widget, highFrequency) {
            this.listeners.push({
                queueId,
                widget,
                highFrequency
            });
        };

        this.unregisterListener = function(widget) {
            this.listeners = this.listeners.filter(listener => listener.widget !== widget);
        };

        this.reloadData = function () {
            const widget = this;
            if (widget.liveConnected) {
                return;
            }

            const queues = [];
            this.listeners.forEach(listener => {
                if (queues.indexOf(listener.queueId) === -1) {
                    queues.push(listener.queueId);
                }
            });

            if (queues.length === 0) {
                console.log("No listeners registered");
                return;
            }

            $.get(
                pollUrl.replace(/QUEUEIDS/, queues.join(",")),
                this.setData.bind(this)
            ).catch(function(err) {
                console.error("Could not load speech queue data from backend", err);
            });
        };

        this.pollReloadData = function () {
            let reloadTimer = 3000;
            if (this.listeners.find(listener => listener.highFrequency)) {
                reloadTimer = 1000;
            }

            const widget = this;

            window.setTimeout(function () {
                widget.reloadData();
                widget.pollReloadData();
            }, reloadTimer);
        }

        this.startPolling = function() {
            this.recalcTimeOffset(new Date());

            this.pollReloadData();

            const widget = this;
            this.timerId = window.setInterval(function () {
                widget.recalcRemainingTime();
            }, 100);

            if (window['ANTRAGSGRUEN_LIVE_EVENTS'] !== undefined) {
                window['ANTRAGSGRUEN_LIVE_EVENTS'].registerListener('user', 'speech', (connectionEvent, speechEvent) => {
                    if (connectionEvent !== null) {
                        widget.liveConnected = connectionEvent;
                    }
                    if (speechEvent !== null) {
                        this.setData([speechEvent]);
                    }
                });
            }
        };

        this.setData = function(data) {
            data.forEach(queue => {
                this.listeners.forEach(listener => {
                    if (listener.queueId === queue.id) {
                        listener.widget.setData(queue);
                    }
                });
            });
        };

        this.recalcRemainingTime = function () {
            this.listeners.forEach(listener => {
               listener.widget.recalcRemainingTime();
            });
        };

        this.startPolling();
    })();


    const SPEECH_COMMON_MIXIN = {
        data() {
            return {
                highFrequency: false,
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
            setHighFrequency: function(highFrequency) {
                this.highFrequency = highFrequency;
            },
            startPolling: function () {
                if (!this.queue) {
                    console.log("No queue set");
                    return;
                }
                SPEECH_POLLER.registerListener(this.queue.id, this, this.highFrequency);
            },
            stopPolling: function () {
                SPEECH_POLLER.unregisterListener(this);
            }
        }
    };
</script>
