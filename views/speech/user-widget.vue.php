<?php

use app\components\UrlHelper;

ob_start();
?>

<article class="speechUser">
    <div v-if="activeSpeaker" class="activeSpeaker">
        <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
        Aktuell: {{ activeSpeaker.name }}
    </div>
    <div v-if="upcomingSpeakers.length > 0" class="upcomingSpeaker">
        <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
        Nächste Redner*innen:
        <ul class="upcomingSpeakerList">
            <li v-for="speaker in upcomingSpeakers">
                {{ speaker.name }}
                <button type="button" class="btn btn-link" v-if="isMe(speaker)" v-on:click="removeMeFromQueue($event)" title="Mich aus der Liste entfernen">
                    <span class="glyphicon glyphicon-trash" aria-label="Mich aus der Liste entfernen"></span>
                </button>
            </li>
        </ul>
    </div>


    <section class="waiting waitingMultiple" v-if="queue.subqueues.length > 1" aria-label="Wartelisten für Redebeiträge">
        <header>
            <span class="glyphicon glyphicon-time"></span>
            Wartelisten
        </header>
        <div class="waitingSubqueues">
            <div v-for="subqueue in queue.subqueues" class="subqueue">
                <div class="name">
                    {{ subqueue.name }}
                </div>
                <div class="applied">
                    {{ subqueue.numApplied }}

                    <div v-if="subqueue.iAmOnList" class="appliedMe">
                        <div class="label label-success">Du</div>
                        <button type="button" class="btn btn-link" v-on:click="removeMeFromQueue($event)" title="Mich aus der Liste entfernen">
                            <span class="glyphicon glyphicon-trash" aria-label="Mich aus der Liste entfernen"></span>
                        </button>
                    </div>
                </div>
                <div class="apply">
                    <button class="btn btn-default btn-xs" type="button"
                            v-if="!queue.iAmOnList && showApplicationForm !== subqueue.id"
                            v-on:click="onShowApplicationForm($event, subqueue)"
                    >
                        Bewerben
                    </button>
                    <form v-on:submit="register($event, subqueue)" v-if="!queue.iAmOnList && showApplicationForm === subqueue.id">
                        <label v-bind:for="'speechRegisterName' + subqueue.id" class="sr-only">Name</label>
                        <div class="input-group">
                            <input type="text" class="form-control" v-model="registerName" v-bind:id="'speechRegisterName' + subqueue.id" ref="adderNameInput">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="submit">Eintragen</button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
</article>


<?php
$html          = ob_get_clean();
$registerUrl   = UrlHelper::createUrl('speech/register');
$unregisterUrl = UrlHelper::createUrl('speech/unregister');
?>

<script>
    Vue.component('speech-user-widget', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf', 'user'],
        data() {
            //console.log(JSON.parse(JSON.stringify(this.queue)));
            return {
                registerName: this.user.name,
                showApplicationForm: null
            };
        },
        computed: {
            activeSpeaker: function () {
                const active = this.queue.slots.filter(function (slot) {
                    return slot.dateStopped === null && slot.dateStarted !== null;
                });
                return (active.length > 0 ? active[0] : null);
            },
            upcomingSpeakers: function () {
                return this.queue.slots.filter(function (slot) {
                    return slot.dateStopped === null && slot.dateStarted === null;
                });
            }
        },
        methods: {
            isMe: function (slot) {
                console.log(JSON.stringify(slot), JSON.stringify(this.user));
                return slot.userId === this.user.id;
            },
            register: function ($event, subqueue) {
                $event.preventDefault();

                const widget = this;
                $.post(<?= json_encode($registerUrl) ?>, {
                    queue: this.queue.id,
                    subqueue: subqueue.id,
                    username: this.registerName,
                    _csrf: this.csrf,
                }, function (data) {
                    if (!data['success']) {
                        alert(data['message']);
                        return;
                    }

                    widget.queue = data['queue'];
                    widget.showApplicationForm = false;
                }).catch(function (err) {
                    alert(err.responseText);
                });
            },
            onShowApplicationForm: function ($event, subqueue) {
                $event.preventDefault();

                this.showApplicationForm = subqueue.id;
                this.$nextTick(function () {
                    this.$refs.adderNameInput[0].focus();
                });
            },
            removeMeFromQueue: function ($event) {
                $event.preventDefault();

                const widget = this;
                $.post(<?= json_encode($unregisterUrl) ?>, {
                    queue: this.queue.id,
                    _csrf: this.csrf,
                }, function (data) {
                    if (!data['success']) {
                        alert(data['message']);
                        return;
                    }

                    widget.queue = data['queue'];
                }).catch(function (err) {
                    alert(err.responseText);
                });
            }
        },
    });
</script>
