<?php

use app\components\UrlHelper;
use yii\helpers\Html;

ob_start();
?>

<article class="speechUser">
    <header class="widgetTitle">
        {{ title }}
        <a v-if="adminUrl" :href="adminUrl" class="speechAdminLink">
            <span class="glyphicon glyphicon-wrench" title="<?= Html::encode(Yii::t('speech', 'goto_admin')) ?>" aria-label="<?= Html::encode(Yii::t('speech', 'goto_admin')) ?>"></span>
        </a>
    </header>

    <div class="activeSpeaker">
        <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
        <span class="title"><?= Yii::t('speech', 'footer_current') ?>:</span>
        <span class="name" v-if="activeSpeaker">
            {{ activeSpeaker.name }}
            <span class="label label-success" v-if="isMe(activeSpeaker)"><?= Yii::t('speech', 'you') ?></span>
        </span>
        <span class="nobody" v-if="!activeSpeaker">
            <?= Yii::t('speech', 'footer_current_nobody') ?>
        </span>
    </div>
    <div v-if="upcomingSpeakers.length > 0" class="upcomingSpeaker">
        <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
        <?= Yii::t('speech', 'next_speaker') ?>:
        <ul class="upcomingSpeakerList">
            <li v-for="speaker in upcomingSpeakers">
                <span class="name">{{ speaker.name }}</span><!-- Fight unwanted whitespace
                --><span class="label label-success" v-if="isMe(speaker)"><?= Yii::t('speech', 'you') ?></span><!-- Fight unwanted whitespace
                -->
                <button type="button" class="btn btn-link btnWithdraw" v-if="isMe(speaker)" @click="removeMeFromQueue($event)"
                        title="<?= Yii::t('speech', 'apply_revoke_aria') ?>" aria-label="<?= Yii::t('speech', 'apply_revoke_aria') ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span class="withdrawLabel"><?= Yii::t('speech', 'apply_revoke') ?></span>
                </button>
            </li>
        </ul>
    </div>

    <section class="waiting waitingSingle" v-if="queue.subqueues.length === 1" aria-label="<?= Yii::t('speech', 'waiting_aria_1') ?>">
        <header>
            <div v-if="queue.subqueues[0].have_applied" class="appliedMe">
                <span class="label label-success" aria-label="<?= Yii::t('speech', 'applied_aria') ?>"><?= Yii::t('speech', 'applied') ?></span>
                <button type="button" class="btn btn-link btnWithdraw" @click="removeMeFromQueue($event)"
                        title="<?= Yii::t('speech', 'apply_revoke_aria') ?>" aria-label="<?= Yii::t('speech', 'apply_revoke_aria') ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span class="withdrawLabel"><?= Yii::t('speech', 'apply_revoke') ?></span>
                </button>
            </div>

            <button class="btn btn-default btn-xs" type="button"
                    v-if="!queue.have_applied && showApplicationForm !== queue.subqueues[0].id"
                    @click="onShowApplicationForm($event, queue.subqueues[0])"
            >
                <?= Yii::t('speech', 'apply') ?>
            </button>
            <form @submit="register($event, queue.subqueues)" v-if="!queue.subqueues[0].have_applied && showApplicationForm === queue.subqueues[0].id">
                <label :for="'speechRegisterName' + queue.subqueues[0].id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control" v-model="registerName" :id="'speechRegisterName' + queue.subqueues[0].id"
                           ref="adderNameInput">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
                    </span>
                </div>
            </form>
        </header>
    </section>

    <section class="waiting waitingMultiple" v-if="queue.subqueues.length > 1" aria-label="<?= Yii::t('speech', 'waiting_aria_x') ?>">
        <div v-for="subqueue in queue.subqueues" class="subqueue">
            <div class="name" v-if="showApplicationForm !== subqueue.id">
                <span class="glyphicon glyphicon-time" aria-label="<?= Yii::t('speech', 'waiting_list') ?>"></span>
                {{ subqueue.name }}
            </div>

            <div class="number" v-if="showApplicationForm !== subqueue.id" :aria-label="numAppliedTitle(subqueue)" :title="numAppliedTitle(subqueue)">{{ subqueue.num_applied }}</div>

            <div v-if="subqueue.have_applied && showApplicationForm !== subqueue.id" class="appliedMe">
                <span class="label label-success" aria-label="<?= Yii::t('speech', 'applied_aria') ?>"><?= Yii::t('speech', 'applied') ?></span>
                <button type="button" class="btn btn-link btnWithdraw" @click="removeMeFromQueue($event)"
                        title="<?= Yii::t('speech', 'apply_revoke_aria') ?>" aria-label="<?= Yii::t('speech', 'apply_revoke_aria') ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span class="withdrawLabel"><?= Yii::t('speech', 'apply_revoke') ?></span>
                </button>
            </div>

            <button class="btn btn-default btn-xs applyBtn" type="button"
                    v-if="queue.is_open && !queue.have_applied && showApplicationForm !== subqueue.id"
                    @click="onShowApplicationForm($event, subqueue)"
            >
                <?= Yii::t('speech', 'apply') ?>
            </button>
            <form @submit="register($event, subqueue)" v-if="queue.is_open && !queue.have_applied && showApplicationForm === subqueue.id">
                <label :for="'speechRegisterName' + subqueue.id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control" v-model="registerName" :id="'speechRegisterName' + subqueue.id" ref="adderNameInput">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
                    </span>
                </div>
            </form>
        </div>
    </section>
</article>


<?php
$html          = ob_get_clean();
$pollUrl       = UrlHelper::createUrl(['/speech/get-queue', 'queueId' => 'QUEUEID']);
$registerUrl   = UrlHelper::createUrl(['/speech/register', 'queueId' => 'QUEUEID']);
$unregisterUrl = UrlHelper::createUrl(['/speech/unregister', 'queueId' => 'QUEUEID']);
?>

<script>
    const pollUrl = <?= json_encode($pollUrl) ?>;
    const registerUrl = <?= json_encode($registerUrl) ?>;
    const unregisterUrl = <?= json_encode($unregisterUrl) ?>;

    Vue.component('speech-user-footer-widget', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf', 'user', 'title', 'adminUrl'],
        data() {
            return {
                registerName: this.user.name,
                showApplicationForm: false, // "null" is already taken by the default form
                pollingId: null
            };
        },
        computed: {
            activeSpeaker: function () {
                const active = this.queue.slots.filter(function (slot) {
                    return slot.date_stopped === null && slot.date_started !== null;
                });
                return (active.length > 0 ? active[0] : null);
            },
            upcomingSpeakers: function () {
                return this.queue.slots.filter(function (slot) {
                    return slot.date_stopped === null && slot.date_started === null;
                });
            }
        },
        methods: {
            isMe: function (slot) {
                return slot.userId === this.user.id;
            },
            numAppliedTitle: function (subqueue) {
                if (subqueue.num_applied === 1) {
                    const msg = "" + <?= json_encode(Yii::t('speech', 'persons_waiting_1')) ?>;
                    return msg;
                } else {
                    const msg = "" + <?= json_encode(Yii::t('speech', 'persons_waiting_x')) ?>;
                    return msg.replace(/%NUM%/, subqueue.num_applied);
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
                    this.$refs.adderNameInput[0].focus();
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
            reloadData: function () {
                const widget = this;
                $.get(pollUrl.replace(/QUEUEID/, widget.queue.id), function (data) {
                    widget.queue = data;
                }).catch(function(err) {
                    console.error("Could not load speech queue data from backend", err);
                });
            },
            startPolling: function () {
                const widget = this;
                this.pollingId = window.setInterval(function () {
                    widget.reloadData();
                }, 3000);
            }
        },
        beforeDestroy() {
            window.clearInterval(this.pollingId)
        },
        created() {
            this.startPolling()
        }
    });
</script>
