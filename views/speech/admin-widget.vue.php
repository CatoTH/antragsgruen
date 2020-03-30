<?php

use app\components\UrlHelper;

ob_start();
?>

<div class="speechAdmin">
    <ol class="slots" aria-label="Redeliste">
        <li v-for="slot in sortedSlots" class="slotEntry">
            {{ slot.name }}
            <button type="button" class="btn btn-sm btn-default" v-on:click="removeSlot($event, slot)">Zurück zur Warteliste</button>
        </li>
        <li class="slotPlaceholder">
            Vorschlag: XYZ
        </li>
    </ol>

    <div class="subqueues">
        <speech-admin-subqueue v-for="subqueue in queue.subqueues"
                               v-bind:subqueue="subqueue"
                               v-on:add-item="onAddItem"
        ></speech-admin-subqueue>
    </div>
</div>

<?php
$html            = ob_get_clean();
$itemSetPosition = UrlHelper::createUrl('speech/admin-item-setposition');
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
                return this.queue.slots.sort(function(slot1, slot2) {
                    console.log(JSON.stringify(slot1), JSON.stringify(slot2));
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
                    return slot2.position - slot1.position;
                });
            }
        },
        methods: {
            _setPosition(id, position) {
                $.post(<?= json_encode($itemSetPosition) ?>, {
                    queue: this.queue.id,
                    item: id,
                    position,
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
            removeSlot: function ($event, slot) {
                $event.preventDefault();
                this._setPosition(slot.id, "remove");
            },
            onAddItem: function (subqueue, item) {
                this._setPosition(item.id, "max");
            }
        }
    });
</script>
