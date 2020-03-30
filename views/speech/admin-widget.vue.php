<?php

use app\components\UrlHelper;

ob_start();
?>

<div class="speechAdmin">
    <ol class="slots" aria-label="Redeliste">
        <li v-for="slot in sortedSlots" class="slotEntry">
            <div class="name">
                {{ slot.name }}
            </div>
            <div class="status statusActive" v-if="slot.dateStarted !== null && slot.dateStopped === null">
                Redebeitrag läuft
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
                <button type="button" class="btn btn-xs btn-default removeSlot" v-on:click="removeSlot($event, slot)">
                    <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                    Warteliste
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
                               v-on:add-item="addItemToSlots"
        ></speech-admin-subqueue>
    </div>
</div>

<?php
$html          = ob_get_clean();
$itemSetStatus = UrlHelper::createUrl('speech/admin-item-setstatus');
?>

<script>
    Vue.component('speech-admin-widget', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf'],
        data() {
            console.log(JSON.parse(JSON.stringify(this.queue)));
            return {};
        },
        computed: {
            sortedSlots: function () {
                return this.queue.slots.sort(function (slot1, slot2) {
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
            slotProposal: function () {
                if (this.queue.subqueues[0].applied.length > 0) {
                    console.log(this.queue.subqueues[0]);
                    return this.queue.subqueues[0].applied[0];
                } else {
                    return null;
                }
            }
        },
        methods: {
            _setStatus: function (id, op) {
                $.post(<?= json_encode($itemSetStatus) ?>, {
                    queue: this.queue.id,
                    item: id,
                    op,
                    _csrf: this.csrf,
                }, (data) => {
                    if (!data['success']) {
                        alert(data['message']);
                        return;
                    }

                    this.queue = data['queue'];
                }).catch(err => {
                    alert(err.responseText);
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
            }
        }
    });
</script>
