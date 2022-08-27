<?php
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

                <!--
                <div class="operations" v-if="otherSubqueues.length > 0">
                    <button class="link moveSubqueue" type="button" v-if="otherSubqueues.length === 1" ref="otherQueue"
                            title="<?= Yii::t('speech', 'admin_subq_move_1') ?>"
                            v-on:click="moveToSubqueue($event, item, otherSubqueues[0])"
                    >
                        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true" v-if="position === 'right'"></span>
                        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true" v-if="position === 'left'"></span>
                        <span class="sr-only"><?= Yii::t('speech', 'admin_subq_move_1') ?></span>
                    </button>

                    <div class="btn-group" v-if="otherSubqueues.length > 1" ref="otherQueues">
                        <button class="link moveSubqueue dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                title="<?= Yii::t('speech', 'admin_subq_move_x') ?>"
                        >
                            <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
                            <span class="sr-only"><?= Yii::t('speech', 'admin_subq_move_x') ?></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li v-for="newSubqueue of otherSubqueues">
                                <a href="#" v-on:click="moveToSubqueue($event, item, newSubqueue)">{{ newSubqueue.name }}</a>
                            </li>
                        </ul>
                    </div>
                </div>
                -->
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
$html = ob_get_clean();
?>

<script>
    __setVueComponent('speech', 'component', 'speech-admin-subqueue', {
        template: <?= json_encode($html) ?>,
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
</script>
