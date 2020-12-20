<?php
ob_start();
?>

<section class="subqueue" aria-label="<?= Yii::t('speech', 'waiting_list') ?> {{ subqueue.name }}"
    :class="{ positionLeft: (position === 'left'), positionRight: (position === 'right') }"
>
    <header v-if="subqueue.name !== 'default'">{{ subqueue.name }} {{ position }}</header>
    <header v-if="subqueue.name === 'default'"><?= Yii::t('speech', 'waiting_list') ?> {{ position }}</header>

    <ul class="subqueueItems">
        <li v-for="item in subqueue.applied" class="subqueueItem" tabindex="0"
            v-on:click="onItemSelected($event, item)"
            v-on:keyup.enter="onItemSelected($event, item)"
            title="<?= Yii::t('speech', 'admin_subq_start') ?>" aria-label="<?= Yii::t('speech', 'admin_subq_start') ?>"
        >
            {{ item.name }}

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
    Vue.component('speech-admin-subqueue', {
        template: <?= json_encode($html) ?>,
        props: ['subqueue', 'allSubqueues', 'position'],
        data() {
            return {
                adderOpened: false,
                adderName: ''
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
                this.$emit('add-item-to-slots-and-start', item);
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
                this.$emit('move-item-to-subqueue', item, newSubqueue);
            }
        }
    });
</script>
