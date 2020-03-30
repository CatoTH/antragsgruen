<?php

use app\components\UrlHelper;

ob_start();
?>

<div>
    <ol class="slots" aria-label="Redeliste">
        <li v-for="slot in queue.slots">
            {{ slot.name }}
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
        computed: {},
        methods: {
            onAddItem: function (subqueue, item) {
                console.log("Adding item", subqueue, item);

                $.post(<?= json_encode($itemSetPosition) ?>, {
                    queue: this.queue.id,
                    subqueue: subqueue.id,
                    item: item.id,
                    position: "max",
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
            }
        }
    });
</script>
