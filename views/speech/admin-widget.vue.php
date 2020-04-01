<?php

use app\components\UrlHelper;

ob_start();
?>

<div class="speechAdmin">
    <section class="previousSpeakers" v-bind:class="">
        <div class="previousSummary">
            <header>
                Bisherige Sprecher*innen: {{ previousSpeakers.length }}

                <button class="btn btn-link" type="button" v-on:click="showPreviousList = true" v-if="!showPreviousList">
                    <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                    Anzeigen
                </button>
                <button class="btn btn-link" type="button" v-on:click="showPreviousList = false" v-if="showPreviousList">
                    <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
                    Anzeigen
                </button>
            </header>

            <div class="previousLists" v-if="showPreviousList">
                <div class="previousList" v-for="subqueue in queue.subqueues">
                    <header v-if="queue.subqueues.length > 1 && subqueue.name !== 'default'"><span>{{ subqueue.name }}</span></header>
                    <header v-if="queue.subqueues.length > 1 && subqueue.name === 'default'"><span>Warteliste</span></header>
                    <ol>
                        <li v-for="item in getPreviousForSubqueue(subqueue)">
                            {{ item.name }}
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <ol class="slots" aria-label="Redeliste">
        <li v-for="slot in sortedSlots" class="slotEntry" v-bind:class="{ isUpcoming: isUpcomingSlot(slot), isActive: isActiveSlot(slot) }">
            <div class="name">
                {{ slot.name }}
            </div>
            <div class="status statusActive" v-if="slot.dateStarted !== null && slot.dateStopped === null">
                Redebeitrag läuft
            </div>
            <div class="status statusUpcoming" v-if="isUpcomingSlot(slot)">
                Nächster Redebeitrag
            </div>

            <button type="button" class="btn btn-success start"
                    v-on:click="startSlot($event, slot)" v-if="slot.dateStarted === null">
                <span class="glyphicon glyphicon-play" aria-label="Redebeitrag starten" title="Redebeitrag starten"></span>
            </button>
            <button type="button" class="btn btn-danger start"
                    v-on:click="stopSlot($event, slot)" v-if="slot.dateStarted !== null && slot.dateStopped === null">
                <span class="glyphicon glyphicon-stop" aria-label="Redebeitrag beenden" title="Redebeitrag beenden"></span>
            </button>

            <div class="operations">
                <button type="button" class="link removeSlot" v-on:click="removeSlot($event, slot)">
                    <span class="glyphicon glyphicon-chevron-down" aria-label="Zurück auf die Warteliste" title="Zurück auf die Warteliste"></span>
                </button>
            </div>
        </li>
        <li class="slotPlaceholder" v-if="slotProposal" tabindex="0"
            v-on:click="addItemToSlots(slotProposal)"
            v-on:keyup.enter="addItemToSlots(slotProposal)">
            Vorschlag: {{ slotProposal.name }}
        </li>
    </ol>

    <div class="subqueues">
        <speech-admin-subqueue v-for="subqueue in queue.subqueues"
                               v-bind:subqueue="subqueue"
                               v-bind:allSubqueues="queue.subqueues"
                               v-on:add-item-to-slots="addItemToSlots"
                               v-on:add-item-to-subqueue="addItemToSubqueue"
                               v-on:move-item-to-subqueue="moveItemToSubqueue"
        ></speech-admin-subqueue>
    </div>
</div>

<?php
$html             = ob_get_clean();
$itemSetStatusUrl = UrlHelper::createUrl('speech/admin-item-setstatus');
$createItemUrl    = UrlHelper::createUrl('speech/admin-create-item');
?>

<script>
    Vue.component('speech-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf'],
        data() {
            console.log(JSON.parse(JSON.stringify(this.queue)));
            return {
                showPreviousList: false
            };
        },
        computed: {
            previousSpeakers: function () {
                return this.queue.slots.filter(function (slot) {
                    return slot.dateStopped !== null;
                }).sort(function (slot1, slot2) {
                    const date1 = new Date(slot1.dateStopped);
                    const date2 = new Date(slot2.dateStopped);
                    return date1.getTime() - date2.getTime();
                });
            },
            sortedSlots: function () {
                console.log("Sorting...");
                return this.queue.slots.filter(function (slot) {
                    return slot.dateStopped === null;
                }).sort(function (slot1, slot2) {
                    if (slot1.dateStarted && slot2.dateStarted === null) {
                        return -1;
                    }
                    if (slot2.dateStarted && slot1.dateStarted === null) {
                        return 1;
                    }
                    if (slot1.dateStarted && slot2.dateStarted) {
                        const date1 = new Date(slot1.dateStarted);
                        const date2 = new Date(slot2.dateStarted);
                        return date1.getTime() - date2.getTime();
                    }
                    return slot1.position - slot2.position;
                });
            },
            activeSlot: function () {
                const active = this.sortedSlots.filter(function (slot) {
                    return slot.dateStarted !== null && slot.dateStopped === null;
                });
                return active.length > 0 ? active[0] : null;
            },
            upcomingSlot: function () {
                const upcoming = this.sortedSlots.filter(function (slot) {
                    return slot.dateStarted === null && slot.dateStopped === null;
                });

                return upcoming.length > 0 ? upcoming[0] : null;
            },
            slotProposal: function () {
                const subqueue = this.queue.subqueues.filter(function (subqueue) {
                    return subqueue.applied.length > 0;
                });
                if (subqueue.length > 0) {
                    return subqueue[0].applied[0];
                } else {
                    return null;
                }
            }
        },
        methods: {
            _setStatus: function (id, op, additionalProps) {
                let postData = {
                    queue: this.queue.id,
                    item: id,
                    op,
                    _csrf: this.csrf,
                };
                if (additionalProps) {
                    postData = Object.assign(postData, additionalProps);
                }
                const widget = this;
                $.post(<?= json_encode($itemSetStatusUrl) ?>, postData, function (data) {
                    if (!data['success']) {
                        alert(data['message']);
                        return;
                    }

                    widget.queue = data['queue'];
                }).catch(function (err) {
                    alert(err.responseText);
                });
            },
            getPreviousForSubqueue: function (subqueue) {
                return this.previousSpeakers.filter(function (item) {
                    return item.subqueue.id === subqueue.id;
                });
            },
            startSlot: function ($event, slot) {
                $event.preventDefault();
                this._setStatus(slot.id, "start");
            },
            stopSlot: function ($event, slot) {
                $event.preventDefault();
                this._setStatus(slot.id, "stop");
            },
            removeSlot: function ($event, slot) {
                $event.preventDefault();
                this._setStatus(slot.id, "unset-slot");
            },
            addItemToSlots: function (item) {
                this._setStatus(item.id, "set-slot");
            },
            moveItemToSubqueue: function (item, newSubqueue) {
                this._setStatus(item.id, "move", {newSubqueueId: newSubqueue.id});
            },
            addItemToSubqueue: function (subqueue, itemName) {
                const widget = this;
                $.post(<?= json_encode($createItemUrl) ?>, {
                    queue: this.queue.id,
                    subqueue: subqueue.id,
                    name: itemName,
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
            },
            isActiveSlot: function (slot) {
                const active = this.activeSlot;
                return (active && active.id === slot.id);
            },
            isUpcomingSlot: function (slot) {
                const active = this.upcomingSlot;
                return (active && active.id === slot.id);
            }
        }
    });
</script>
