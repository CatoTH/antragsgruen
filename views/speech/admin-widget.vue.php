<?php

use app\components\UrlHelper;
use yii\helpers\Html;

ob_start();
$componentAdminLink = UrlHelper::createUrl('admin/index/appearance') . '#hasSpeechLists';
?>

<article class="speechAdmin" :class="{dragging: dragging}">
    <div class="toolbarBelowTitle settings">
        <div class="settingsActive" v-if="queue.is_active">
            <span class="glyphicon glyphicon-ok" aria-hidden="true"></span>
            <?= Yii::t('speech', 'admin_is_active') ?>

            <button class="btn btn-xs btn-default" type="button" @click="setInactive()">
                <?= Yii::t('speech', 'admin_deactivate') ?>
            </button>
        </div>
        <div class="settingsActive" v-if="!queue.is_active">
            <span class="inactive"><?= Yii::t('speech', 'admin_is_inactive') ?></span>
            <button class="btn btn-xs btn-default" type="button" @click="setActive()">
                <?= Yii::t('speech', 'admin_activate') ?>
            </button>
            <span v-if="queue.other_active_name" class="deactivateOthers">(<?= Yii::t('speech', 'admin_deactivate_other') ?>)</span>
        </div>
        <div class="settingsOpen">
            <label class="settingOpen" v-if="queue.is_active">
                <input type="checkbox" v-model="queue.settings.is_open" @change="settingsChanged()">
                <?= Yii::t('speech', 'admin_setting_open') ?>
            </label>
            <label class="settingOpenPoo" v-if="queue.is_active">
                <input type="checkbox" v-model="queue.settings.is_open_poo" @change="settingsChanged()">
                <?= Yii::t('speech', 'admin_setting_open_poo') ?>
            </label>
        </div>
        <div class="settingsPolicy">
            <div class="btn-group">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <?= Yii::t('speech', 'admin_setting') ?>
                    <span class="caret" aria-hidden="true"></span>
                </button>
                <ul class="dropdown-menu">
                    <li class="checkbox">
                        <label @click="$event.stopPropagation()">
                            <input type="checkbox" class="allowCustomNames" v-model="queue.settings.allow_custom_names" @change="settingsChanged()">
                            <?= Yii::t('speech', 'admin_allow_custom_names') ?>
                        </label>
                    </li>
                    <li class="checkbox">
                        <label @click="$event.stopPropagation()">
                            <input type="checkbox" class="preferNonspeaker" v-model="queue.settings.prefer_nonspeaker" @change="settingsChanged()">
                            <?= Yii::t('speech', 'admin_prefer_nonspeak') ?>
                        </label>
                    </li>
                    <li class="checkbox">
                        <label @click="$event.stopPropagation()">
                            <input type="checkbox" class="showNames" v-model="queue.settings.show_names" @change="settingsChanged()">
                            <?= Yii::t('speech', 'admin_show_names') ?>
                        </label>
                    </li>
                    <li class="checkbox">
                        <label @click="$event.stopPropagation()">
                            <input type="checkbox" class="hasSpeakingTime" v-model="hasSpeakingTime" @change="settingsChanged()">
                            <?= Yii::t('speech', 'admin_speaking_time') ?>
                        </label>
                    </li>
                    <li v-if="hasSpeakingTime" class="speakingTime">
                        <label @click="$event.stopPropagation()" class="input-group input-group-sm">
                            <input type="number" class="form-control" v-model="speakingTime" @change="settingsChanged()">
                            <span class="input-group-addon"><?= Yii::t('speech', 'admin_time_seconds') ?></span>
                        </label>
                    </li>
                    <li class="link">
                        <a href="<?= Html::encode($componentAdminLink) ?>">
                            <span class="icon glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                            <?= Yii::t('speech', 'admin_goto_components') ?>
                        </a>
                    </li>
                    <li class="randomizeQueues">
                        <button class="btn btn-default btn-sm" type="button" @click="randomizeQueues($event)">
                            <?= Yii::t('speech', 'admin_randomize_queues') ?>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <main class="content">
        <section class="previousSpeakers" :class="{previousShown: showPreviousList, invisible: !hasPreviousSpeakers}">
            <header>
                <?= Yii::t('speech', 'admin_prev_speakers') ?>: {{ previousSpeakers.length }}

                <button class="btn btn-link" type="button" @click="showPreviousList = true" v-if="!showPreviousList">
                    <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                    <?= Yii::t('speech', 'admin_prev_show') ?>
                </button>
                <button class="btn btn-link" type="button" @click="showPreviousList = false" v-if="showPreviousList">
                    <span class="glyphicon glyphicon-chevron-up" aria-hidden="true"></span>
                    <?= Yii::t('speech', 'admin_prev_show') ?>
                </button>
            </header>

            <div class="previousLists" v-if="showPreviousList">
                <div class="previousList" v-for="subqueue in queue.subqueues">
                    <header v-if="queue.subqueues.length > 1 && subqueue.name !== 'default'"><span>{{ subqueue.name }}</span></header>
                    <header v-if="queue.subqueues.length > 1 && subqueue.name === 'default'"><span><?= Yii::t('speech', 'waiting_list_1') ?></span></header>
                    <ol>
                        <li v-for="item in getPreviousForSubqueue(subqueue)" v-html="formatUsernameHtml(item)"></li>
                    </ol>
                </div>
            </div>
        </section>

        <ol class="slots" aria-label="<?= Yii::t('speech', 'speaking_list') ?>">
            <li v-if="activeSlot" class="slotEntry slotActive active">
                <span class="glyphicon glyphicon-comment iconBackground" aria-hidden="true"></span>

                <div class="status statusActive">
                    <?= Yii::t('speech', 'admin_running') ?>:
                </div>

                <div class="name" v-html="formatUsernameHtml(activeSlot)"></div>

                <div class="remainingTime" v-if="hasSpeakingTime && remainingSpeakingTime !== null">
                    <?= Yii::t('speech', 'remaining_time') ?>:
                    <span v-if="remainingSpeakingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
                    <span v-if="remainingSpeakingTime < 0" class="over"><?= Yii::t('speech', 'remaining_time_over') ?></span>
                </div>

                <button type="button" class="btn btn-danger stop" @click="stopSlot($event, activeSlot)">
                    <span class="glyphicon glyphicon-stop" title="<?= Yii::t('speech', 'admin_stop') ?>" aria-hidden="true"></span>
                    <span class="sr-only"><?= Yii::t('speech', 'admin_stop') ?></span>
                </button>

                <div class="operations">
                    <button type="button" class="link removeSlot" @click="removeSlot($event, activeSlot)" title="<?= Yii::t('speech', 'admin_back_to_wait') ?>">
                        <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                        <span class="sr-only"><?= Yii::t('speech', 'admin_back_to_wait') ?></span>
                    </button>
                </div>
            </li>
            <li v-if="!activeSlot" class="slotEntry slotActive inactive">
                <span class="glyphicon glyphicon-comment iconBackground" aria-hidden="true"></span>

                <div class="status statusActive">
                    <?= Yii::t('speech', 'admin_running') ?>:
                </div>

                <div class="nameNobody">
                    <?= Yii::t('speech', 'admin_running_nobody') ?>
                </div>
            </li>

            <li v-for="slot in sortedQueue" class="slotEntry">
                <span class="glyphicon glyphicon-time iconBackground" aria-hidden="true"></span>

                <div class="name" v-html="formatUsernameHtml(slot)"></div>

                <button type="button" class="btn btn-success start" @click="startSlot($event, slot)" title="<?= Yii::t('speech', 'admin_start') ?>">
                    <span class="glyphicon glyphicon-play" aria-hidden="true"></span>
                    <span class="sr-only"><?= Yii::t('speech', 'admin_start') ?></span>
                </button>

                <div class="operations">
                    <button type="button" class="link removeSlot" @click="removeSlot($event, slot)" title="<?= Yii::t('speech', 'admin_back_to_wait') ?>">
                        <span class="glyphicon glyphicon-chevron-down" aria-hidden="true"></span>
                        <span class="sr-only"><?= Yii::t('speech', 'admin_back_to_wait') ?></span>
                    </button>
                </div>
            </li>

            <li class="slotPlaceholder active" v-if="slotProposal"
                @click="addItemToSlotsAndStart(slotProposal.id)"
                @keyup.enter="addItemToSlotsAndStart(slotProposal.id)">
                <span class="glyphicon glyphicon-time iconBackground" aria-hidden="true"></span>
                <div class="title"><?= Yii::t('speech', 'admin_next') ?>:</div>
                <div class="name" v-html="formatUsernameHtml(slotProposal)"></div>

                <div class="operationDelete" @click="onItemDelete($event, slotProposal.id)" @keyup.enter="onItemDelete($event, item)" tabindex="0">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span><?= Yii::t('speech', 'admin_delete') ?></span>
                </div>

                <div class="operationStart" @click="addItemToSlotsAndStart(slotProposal.id)" @keyup.enter="addItemToSlotsAndStart(slotProposal.id)" tabindex="0">
                    <span class="glyphicon glyphicon-play" aria-hidden="true"></span>
                    <span><?= Yii::t('speech', 'admin_start') ?></span>
                </div>
            </li>
            <li class="slotPlaceholder inactive" v-if="!slotProposal">
                <span class="glyphicon glyphicon-time iconBackground" aria-hidden="true"></span>
                <div class="title"><?= Yii::t('speech', 'admin_start_proposal') ?>:</div>
                <div class="nameNobody"><?= Yii::t('speech', 'admin_proposal_nobody') ?></div>
            </li>
        </ol>

        <div class="subqueues">
            <speech-admin-subqueue v-for="(subqueue, index) in queue.subqueues"
                                   :subqueue="subqueue"
                                   :allSubqueues="queue.subqueues"
                                   :position="index > 0 ? 'right' : 'left'"
                                   @add-item-to-slots-and-start="addItemToSlotsAndStart"
                                   @add-item-to-subqueue="addItemToSubqueue"
                                   @delete-item="deleteItem"
                                   @move-item-to-subqueue="moveItemToSubqueue"
                                   @item-drag-start="itemDragStart"
                                   @item-drag-end="itemDragEnd"
                                   ref="subqueueWidgets"
            ></speech-admin-subqueue>
        </div>
    </main>

    <section class="queueResetSection">
        <button type="button" class="btn btn-link btn-danger" @click="resetQueue()">
            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
            <?= Yii::t('speech', 'admin_reset') ?>
        </button>
    </section>
</article>

<?php
$speechAdminWidgetHtml = ob_get_clean();

ob_start();
?>
<section class="subqueue" aria-label="<?= Yii::t('speech', 'waiting_list') ?> {{ subqueue.name }}"
         :class="{ positionLeft: (position === 'left'), positionRight: (position === 'right') }"
>
    <header v-if="subqueue.name !== 'default'">{{ subqueue.name }}</header>
    <header v-if="subqueue.name === 'default'"><?= Yii::t('speech', 'waiting_list') ?></header>

    <ul class="subqueueItems">
        <template v-for="(item, index) in subqueue.applied">
            <li class="dropPlaceholder" :class="{hovered: (index === hoveredPlaceholder), hoverable: isHoverable(index)}">
                <div class="dropAdditionalSpace"
                     @dragenter="onPlaceholderDragEnter($event, index)" @dragleave="onPlaceholderDragLeave($event, index)"
                     @drop="onPlaceholderDrop($event, index)" @dragover.prevent></div>
                <div class="hoveredIndicator"><?= Yii::t('speech', 'admin_move_here') ?></div>
            </li>
            <li class="subqueueItem" draggable="true" @dragstart="onItemDragStart($event, item)" @dragend="onItemDragEnd($event, item)">
                <div class="starter">
                    <span v-html="formatUsernameHtml(item)"></span>

                    <div class="operationDelete" @click="onItemDelete($event, item)" @keyup.enter="onItemDelete($event, item)" tabindex="0">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                        <span><?= Yii::t('speech', 'admin_delete') ?></span>
                    </div>

                    <div class="operationsIndicator operationStart" tabindex="0"
                         @click="onItemSelected($event, item)"
                         @keyup.enter="onItemSelected($event, item)"
                         title="<?= Yii::t('speech', 'admin_subq_start') ?>" aria-label="<?= Yii::t('speech', 'admin_subq_start') ?>">
                        <span class="glyphicon glyphicon-play" aria-hidden="true"></span>
                        <span><?= Yii::t('speech', 'admin_start') ?></span>
                    </div>
                </div>
            </li>
        </template>
        <li class="dropPlaceholder" :class="{hovered: (subqueue.applied.length === hoveredPlaceholder), hoverable: isHoverable(subqueue.applied.length)}">
            <div class="dropAdditionalSpace"
                 @dragenter="onPlaceholderDragEnter($event, subqueue.applied.length)" @dragleave="onPlaceholderDragLeave($event, subqueue.applied.length)"
                 @drop="onPlaceholderDrop($event, subqueue.applied.length)" @dragover.prevent
            ></div>
            <div class="hoveredIndicator"><?= Yii::t('speech', 'admin_move_here') ?></div>
        </li>
    </ul>

    <div class="empty" v-if="subqueue.applied.length === 0">
        <?= Yii::t('speech', 'admin_subq_no_applic') ?>
    </div>

    <section class="subqueueAdder">
        <button class="link adderOpener" type="button" v-if="!adderOpened" v-on:click="openAdder()" v-on:keyup.enter="openAdder()">
            <span class="glyphicon glyphicon-plus" aria-hidden="true"></span>
            <?= Yii::t('speech', 'admin_subq_add') ?>
        </button>
        <form method="POST" v-on:submit="onAdderSubmitted($event)" v-if="adderOpened">
            <label v-bind:for="'subqueueAdderName' + subqueue.id" class="sr-only"><?= Yii::t('speech', 'admin_subq_name') ?></label>
            <div class="input-group">
                <input type="text" class="form-control" ref="adderNameInput" v-model="adderName" v-bind:id="'subqueueAdderName' + subqueue.id"
                       required placeholder="<?= Yii::t('speech', 'admin_subq_name') ?>" title="<?= Yii::t('speech', 'admin_subq_name') ?>">
                <span class="input-group-btn">
                    <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'admin_subq_add') ?></button>
                </span>
            </div>
        </form>
    </section>
</section>
<?php
$speechAdminSubqueueHtml = ob_get_clean();


$setStatusUrl      = UrlHelper::createUrl(['/speech/post-queue-settings', 'queueId' => 'QUEUEID']);
$itemPerformOpUrl  = UrlHelper::createUrl(['/speech/post-item-operation', 'queueId' => 'QUEUEID', 'itemId' => 'ITEMID', 'op' => 'OPERATION']);
$createItemUrl     = UrlHelper::createUrl(['/speech/admin-create-item', 'queueId' => 'QUEUEID']);
$resetQueueUrl     = UrlHelper::createUrl(['/speech/admin-queue-reset', 'queueId' => 'QUEUEID']);
$randomizeQueueUrl = UrlHelper::createUrl(['/speech/admin-queue-randomize', 'queueId' => 'QUEUEID']);
$pollUrl           = UrlHelper::createUrl(['/speech/get-queue-admin', 'queueId' => 'QUEUEID']);
?>

<script type="module">
    import { createApp } from '/npm/vue.esm-browser.prod.js';

    const pollUrl = <?= json_encode($pollUrl) ?>;
    const setStatusUrl = <?= json_encode($setStatusUrl) ?>;
    const createItemUrl = <?= json_encode($createItemUrl) ?>;
    const resetQueueUrl = <?= json_encode($resetQueueUrl) ?>;
    const randomizeQueueUrl = <?= json_encode($randomizeQueueUrl) ?>;
    const itemPerformOperationUrl = <?= json_encode($itemPerformOpUrl) ?>;
    const resetConfirmation = <?= json_encode(Yii::t('speech', 'admin_reset_dialog')) ?>;

    const $element = $(".manageSpeechQueueWidget");

    /** @type {import('vue').App} */
    const widget = createApp({
            template: `<speech-admin-widget :initQueue="queue" :csrf="csrf"></speech-admin-widget>`,
            data() { return {
                queue: $element.data("queue"),
                csrf: $("head").find("meta[name=csrf-token]").attr("content"),
        } }
    });

    widget.component('speech-admin-subqueue', {
        template: <?= json_encode($speechAdminSubqueueHtml) ?>,
        props: ['subqueue', 'allSubqueues', 'position'],
        data() {
            return {
                adderOpened: false,
                adderName: '',
                hoveredPlaceholder: null,
                draggingRightNow: null,
                dragdropHoverCache: {}
            };
        },
        computed: {
            otherSubqueues: function () {
                const mySubqueueId = this.subqueue.id;
                return this.allSubqueues.filter(function (subqueue) {
                    return subqueue.id && subqueue.id !== mySubqueueId
                });
            }
        },
        methods: {
            _isButtonClick: function ($event) {
                let isButton = false;
                if (this.$refs.otherQueues) {
                    this.$refs.otherQueues.forEach(function (button) {
                        if ($event.target === button || button.contains($event.target)) {
                            isButton = true;
                        }
                    });
                }
                if (this.$refs.otherQueue) {
                    this.$refs.otherQueue.forEach(function (button) {
                        if ($event.target === button || button.contains($event.target)) {
                            isButton = true;
                        }
                    });
                }
                return isButton;
            },
            onItemSelected: function ($event, item) {
                if (this._isButtonClick($event)) {
                    return;
                }
                $event.preventDefault();
                // this.$emit('add-item-to-slots', item);
                this.$emit('add-item-to-slots-and-start', item.id);
            },
            onItemDelete: function ($event, item) {
                $event.preventDefault();
                $event.stopPropagation();
                this.$emit('delete-item', item.id);
            },
            openAdder: function () {
                this.adderOpened = true;
                this.$nextTick(function () {
                    this.$refs.adderNameInput.focus();
                });
            },
            onAdderSubmitted: function ($event) {
                $event.preventDefault();
                if (this.adderName) {
                    this.$emit('add-item-to-subqueue', this.subqueue, this.adderName);
                    this.adderOpened = false;
                    this.adderName = '';
                }
            },
            moveToSubqueue: function ($event, item, newSubqueue) {
                $event.preventDefault();
                this.$emit('move-item-to-subqueue', item.id, newSubqueue.id);
            },

            isHoverable: function (placeholderIndex) {
                let hoverable = true;
                if (placeholderIndex > 0 && this.subqueue.applied[placeholderIndex - 1].id === this.draggingRightNow) {
                    hoverable = false;
                }
                if (placeholderIndex < this.subqueue.applied.length && this.subqueue.applied[placeholderIndex].id === this.draggingRightNow) {
                    hoverable = false;
                }
                return hoverable;
            },

            formatUsernameHtml: function (item) {
                let name = item.name;
                name = name.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");

                // Replaces patterns like [[Remote]] by labels.
                return name.replaceAll(/\[\[(.*)]]/g, "<span class=\"label label-info\">$1</span>");
            },

            // When an item of this list gets dragged
            onItemDragStart: function ($event, item) {
                $event.dataTransfer.setData('itemid', item.id);
                this.$emit('item-drag-start', item.id);
            },
            onItemDragEnd: function ($event, item) {
                this.$emit('item-drag-end', item.id);
            },

            // When any item starts to get dragged (triggered from admin-widget.vue)
            onWidgetDragStart: function (itemId) {
                this.draggingRightNow = itemId;
            },
            onWidgetDragEnd: function () {
                this.draggingRightNow = null;
                this.hoveredPlaceholder = null;
                this.dragdropHoverCache = {};
            },

            // When an item gets dragged over a placeholder here
            onPlaceholderDragEnter: function ($event, index) {
                if ($event.dataTransfer.items.length === 0 || $event.dataTransfer.items[0].type !== 'itemid') {
                    return;
                }
                this.dragdropHoverCache[index] = (this.dragdropHoverCache[index] === undefined ? 1 : this.dragdropHoverCache[index] + 1);
                this.hoveredPlaceholder = index;
            },
            onPlaceholderDragLeave: function ($event, index) {
                if ($event.dataTransfer.items.length === 0 || $event.dataTransfer.items[0].type !== 'itemid') {
                    return;
                }
                this.dragdropHoverCache[index] = (this.dragdropHoverCache[index] === undefined ? 0 : this.dragdropHoverCache[index] - 1);
                if (this.dragdropHoverCache[index] !== 0) {
                    // enter was called twice, so leave has to be called twice as well
                    return;
                }
                if (this.hoveredPlaceholder === index) {
                    this.hoveredPlaceholder = null;
                }
            },
            onPlaceholderDrop: function ($event, index) {
                if ($event.dataTransfer.items.length === 0 || $event.dataTransfer.items[0].type !== 'itemid') {
                    return;
                }
                $event.dataTransfer.items[0].getAsString(function(itemid) {
                    this.$emit('move-item-to-subqueue', itemid, this.subqueue.id, index);
                }.bind(this));
            }
        }
    });

    widget.component('speech-admin-widget', {
        template: <?= json_encode($speechAdminWidgetHtml) ?>,
        props: ['initQueue', 'csrf'],
        data() {
            return {
                queue: null,
                showPreviousList: false,
                pollingId: null,
                liveConnected: false,
                timerId: null,
                dragging: false,
                changedSettings: {
                    hasSpeakingTime: null,
                    speakingTime: null
                },
                remainingSpeakingTime: null
            };
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
            hasSpeakingTime: {
                get: function () {
                    return (this.changedSettings.hasSpeakingTime !== null ? this.changedSettings.hasSpeakingTime : this.queue.settings.speaking_time !== null);
                },
                set: function (value) {
                    this.changedSettings.hasSpeakingTime = value;
                }
            },
            speakingTime: {
                get: function () {
                    return (this.changedSettings.speakingTime !== null ? this.changedSettings.speakingTime : this.queue.settings.speaking_time);
                },
                set: function (value) {
                    this.changedSettings.speakingTime = value;
                }
            },
            previousSpeakers: function () {
                return this.queue.slots.filter(function (slot) {
                    return slot.date_stopped !== null;
                }).sort(function (slot1, slot2) {
                    const date1 = new Date(slot1.date_stopped);
                    const date2 = new Date(slot2.date_stopped);
                    return date1.getTime() - date2.getTime();
                });
            },
            hasPreviousSpeakers: function () {
                return this.queue.slots.filter(function (slot) {
                    return slot.date_stopped !== null;
                }).length > 0;
            },
            formattedRemainingTime: function () {
                const minutes = Math.floor(this.remainingSpeakingTime / 60);
                let seconds = this.remainingSpeakingTime - minutes * 60;
                if (seconds < 10) {
                    seconds = "0" + seconds;
                }

                return minutes + ":" + seconds;
            },
            sortedQueue: function () {
                return this.queue.slots.filter(function (slot) {
                    return slot.date_started === null;
                }).sort(function (slot1, slot2) {
                    if (slot1.is_point_of_order && !slot2.is_point_of_order) {
                        return -1;
                    }
                    if (slot2.is_point_of_order && !slot1.is_point_of_order) {
                        return 1;
                    }
                    return slot1.position - slot2.position;
                });
            },
            activeSlot: function () {
                const active = this.queue.slots.filter(function (slot) {
                    return slot.date_started !== null && slot.date_stopped === null;
                });
                return active.length > 0 ? active[0] : null;
            },
            slotProposal: function () {
                const prevSpeakerNums = {};
                this.queue.slots.forEach(function (slot) {
                    if (prevSpeakerNums[slot.subqueue.id] === undefined) {
                        prevSpeakerNums[slot.subqueue.id] = 0;
                    }
                    prevSpeakerNums[slot.subqueue.id]++;
                });

                const queuesSortedByPreviousSpeakers = Object.assign([], this.queue.subqueues).sort(function (queue1, queue2) {
                    const num1 = (prevSpeakerNums[queue1.id] === undefined ? 0 : prevSpeakerNums[queue1.id]);
                    const num2 = (prevSpeakerNums[queue2.id] === undefined ? 0 : prevSpeakerNums[queue2.id]);
                    if (num1 === num2) {
                        return queue1.position - queue2.position; // First positions come first, if same amount of speakers
                    } else {
                        return num1 - num2; // Lower numbers first
                    }
                });

                // If queue no. 1 is empty and had less previous talkers than queue no. 2, we're not making a proposal

                const chosenQueue = queuesSortedByPreviousSpeakers[0];
                if (chosenQueue.applied.length === null) {
                    return null;
                }

                if (this.queue.settings.prefer_nonspeaker) {
                    // @TODO respect user ID + name
                    return chosenQueue.applied[0];
                } else {
                    return chosenQueue.applied[0];
                }
            }
        },
        methods: {
            _performOperation: function (itemId, op, additionalProps) {
                let postData = {
                    _csrf: this.csrf,
                };
                if (additionalProps) {
                    postData = Object.assign(postData, additionalProps);
                }
                const widget = this;
                const url = itemPerformOperationUrl
                    .replace(/QUEUEID/, widget.queue.id)
                    .replace(/ITEMID/, itemId)
                    .replace(/OPERATION/, op);
                $.post(url, postData, function (data) {
                    widget.queue = data;
                }).catch(function (err) {
                    alert(err.responseText);
                });
            },
            getPreviousForSubqueue: function (subqueue) {
                return this.previousSpeakers.filter(function (item) {
                    return item.subqueue.id === subqueue.id ||
                        (item.subqueue.id === null && subqueue.id === -1);
                });
            },
            startSlot: function ($event, slot) {
                $event.preventDefault();
                this._performOperation(slot.id, "start");
            },
            stopSlot: function ($event, slot) {
                $event.preventDefault();
                this._performOperation(slot.id, "stop");
            },
            removeSlot: function ($event, slot) {
                $event.preventDefault();
                this._performOperation(slot.id, "unset-slot");
            },
            // Not used currently
            addItemToSlots: function (item) {
                this._performOperation(item.id, "set-slot");
            },
            addItemToSlotsAndStart: function (itemId) {
                this._performOperation(itemId, "set-slot-and-start");
            },
            onItemDelete: function ($event, itemId) {
                $event.preventDefault();
                $event.stopPropagation();
                this._performOperation(itemId, "delete");
            },
            deleteItem: function (itemId) {
                this._performOperation(itemId, "delete");
            },
            moveItemToSubqueue: function (itemId, newSubqueueId, position) {
                this._performOperation(itemId, "move", {newSubqueueId, position});
                this.itemDragEnd();
            },
            itemDragStart: function (itemId) {
                this.dragging = true;
                this.$refs.subqueueWidgets.forEach(subqueue => {
                    subqueue.onWidgetDragStart.bind(subqueue)(itemId);
                });
            },
            itemDragEnd: function () {
                this.dragging = false;
                this.$refs.subqueueWidgets.forEach(subqueue => {
                    subqueue.onWidgetDragEnd.bind(subqueue)();
                });
            },
            setInactive: function () {
              this.queue.is_active = false;
              this.settingsChanged();
            },
            setActive: function () {
              this.queue.is_active = true;
              this.settingsChanged();
            },
            resetQueue: function () {
                const widget = this;
                bootbox.confirm(resetConfirmation, function(result) {
                    if (result) {
                        $.post(resetQueueUrl.replace(/QUEUEID/, widget.queue.id), { _csrf: widget.csrf }, function (data) {
                            widget.queue = data;
                        }).catch(function (err) {
                            alert(err.responseText);
                        });
                    }
                });
            },
            settingsChanged: function () {
                const widget = this;
                $.post(setStatusUrl.replace(/QUEUEID/, widget.queue.id), {
                    is_active: (this.queue.is_active ? 1 : 0),
                    is_open: (this.queue.settings.is_open ? 1 : 0),
                    is_open_poo: (this.queue.settings.is_open_poo ? 1 : 0),
                    prefer_nonspeaker: (this.queue.settings.prefer_nonspeaker ? 1 : 0),
                    allow_custom_names: (this.queue.settings.allow_custom_names ? 1 : 0),
                    show_names: (this.queue.settings.show_names ? 1 : 0),
                    speaking_time: (this.hasSpeakingTime ? (this.speakingTime > 0 ? parseInt(this.speakingTime, 10) : 60) : null),
                    _csrf: this.csrf,
                }, function (data) {
                    widget.queue = data['queue'];

                    widget.changedSettings.speakingTime = null;
                    widget.changedSettings.hasSpeakingTime = null;

                    if (data['sidebar'] && data['sidebar'][0] !== '') {
                        document.getElementById('sidebar').childNodes.item(0).innerHTML = data['sidebar'][0];
                        // @TODO Secondary sidebar
                    }
                }).catch(function (err) {
                    alert(err.responseText);
                });
            },
            randomizeQueues: function ($event) {
                $event.preventDefault();
                $event.stopPropagation();
                const widget = this;
                $.post(randomizeQueueUrl.replace(/QUEUEID/, widget.queue.id), { _csrf: widget.csrf }, function (data) {
                    widget.queue = data;
                }).catch(function (err) {
                    alert(err.responseText);
                });
            },
            addItemToSubqueue: function (subqueue, itemName) {
                const widget = this;
                $.post(createItemUrl.replace(/QUEUEID/, widget.queue.id), {
                    subqueue: subqueue.id,
                    name: itemName,
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
                const active = this.activeSlot;
                if (!active) {
                    this.remainingSpeakingTime = null;
                    return;
                }
                const startedTs = (new Date(active.date_started)).getTime();
                const currentTs = (new Date()).getTime() - this.timeOffset;
                const secondsPassed = Math.round((currentTs - startedTs) / 1000);

                this.remainingSpeakingTime = this.queue.settings.speaking_time - secondsPassed;
            },
            setData: function (data) {
                this.queue = data;
                this.recalcTimeOffset(new Date(data['current_time']));
                this.recalcRemainingTime();
            },
            reloadData: function () {
                const widget = this;
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
            formatUsernameHtml: function (item) {
                let name = item.name;
                name = name.replace(/&/g, "&amp;").replace(/>/g, "&gt;").replace(/</g, "&lt;").replace(/"/g, "&quot;");

                // Replaces patterns like [[Remote]] by labels.
                return name.replaceAll(/\[\[(.*)]]/g, "<span class=\"label label-info\">$1</span>");
            },
            startPolling: function () {
                this.recalcTimeOffset(new Date());

                const widget = this;
                this.pollingId = window.setInterval(function () {
                    widget.reloadData();
                }, 1000);

                this.timerId = window.setInterval(function () {
                    widget.recalcRemainingTime();
                }, 100);

                if (window['ANTRAGSGRUEN_LIVE_EVENTS'] !== undefined) {
                    window['ANTRAGSGRUEN_LIVE_EVENTS'].registerListener('admin', 'speech', (connectionEvent, speechEvent) => {
                        if (connectionEvent !== null) {
                            widget.liveConnected = connectionEvent;
                        }
                        if (speechEvent !== null) {
                            this.setData(speechEvent);
                        }
                    });
                }
            }
        },
        beforeUnmount() {
            window.clearInterval(this.pollingId);
            window.clearInterval(this.timerId);
        },
        created() {
            this.startPolling();
        }
    });

    widget.config.compilerOptions.whitespace = 'condense';
    widget.mount(".manageSpeechQueueWidget .speechAdmin");
</script>
