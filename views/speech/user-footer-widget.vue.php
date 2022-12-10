<?php

use app\components\UrlHelper;
use yii\helpers\Html;

$loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url]);

ob_start();
?>

<article class="speechUser" :class="{'multiple-queues': queue.subqueues.length > 1, 'single-queue': queue.subqueues.length === 1}">
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
            <span v-html="formatUsernameHtml(activeSpeaker)"></span>
            <span class="label label-success" v-if="isMe(activeSpeaker)"><?= Yii::t('speech', 'you') ?></span>
        </span>
        <span class="nobody" v-if="!activeSpeaker">
            <?= Yii::t('speech', 'footer_current_nobody') ?>
        </span>
        <div class="remainingTime" v-if="activeSpeaker && hasSpeakingTime && remainingSpeakingTime !== null">
            <span v-if="remainingSpeakingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
            <span v-if="remainingSpeakingTime < 0" class="over"><?= Yii::t('speech', 'remaining_time_over') ?></span>
        </div>
    </div>
    <div v-if="upcomingSpeakers.length > 0" class="upcomingSpeaker">
        <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
        <?= Yii::t('speech', 'next_speaker') ?>:
        <ul class="upcomingSpeakerList">
            <li v-for="speaker in upcomingSpeakers">
                <span class="name" v-html="formatUsernameHtml(speaker)"></span><!-- Fight unwanted whitespace
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

            <div class="notPossible" v-if="!queue.is_open">
                <?= Yii::t('speech', 'apply_closed') ?>
            </div>

            <!-- Regular waiting lists -->
            <form @submit="register($event, queue.subqueues, false)" v-if="queue.is_open && !queue.have_applied && !queue.allow_custom_names && registerName">
                <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
            </form>

            <button class="btn btn-default btn-xs btnApply" type="button"
                    v-if="queue.is_open && !queue.have_applied && showApplicationForm !== queue.subqueues[0].id && showApplicationForm !== queue.subqueues[0].id + '_poo' && !loginWarning && !(!queue.allow_custom_names && registerName)"
                    @click="onShowApplicationForm($event, queue.subqueues[0], false)"
            >
                <?= Yii::t('speech', 'apply') ?>
            </button>
            <a href="<?= Html::encode($loginUrl) ?>" class="loginWarning" v-if="loginWarning">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                <?= Yii::t('speech', 'login_warning') ?>
            </a>

            <form @submit="register($event, queue.subqueues, false)" v-if="!queue.subqueues[0].have_applied && showApplicationForm === queue.subqueues[0].id">
                <label :for="'speechRegisterName' + queue.subqueues[0].id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control speechRegisterName" v-model="registerName" :id="'speechRegisterName' + queue.subqueues[0].id"
                           ref="adderNameInput">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
                    </span>
                </div>
            </form>

            <!-- Point of Order -->

            <form @submit="register($event, queue.subqueues, true)" v-if="queue.is_open_poo && !queue.have_applied && !queue.allow_custom_names && registerName">
                <button class="btn btn-link btn-sm applyOpenerPoo" type="submit">
                    <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                    <?= Yii::t('speech', 'apply_poo_do') ?>
                </button>
            </form>

            <button class="btn btn-link btn-xs applyOpenerPoo" type="button"
                    v-if="queue.is_open_poo && !queue.have_applied && showApplicationForm !== queue.subqueues[0].id && showApplicationForm !== (queue.subqueues[0].id + '_poo') && !(!queue.allow_custom_names && registerName)"
                    :disabled="loginWarning"
                    @click="onShowApplicationForm($event, queue.subqueues[0], true)"
            >
                <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                <?= Yii::t('speech', 'apply_poo_do') ?>
            </button>

            <form @submit="register($event, queue.subqueues, true)" v-if="!queue.subqueues[0].have_applied && showApplicationForm === (queue.subqueues[0].id + '_poo')">
                <label :for="'speechRegisterName' + queue.subqueues[0].id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control speechRegisterName" v-model="registerName" :id="'speechRegisterName' + queue.subqueues[0].id" ref="adderNameInput">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_poo_do') ?></button>
                    </span>
                </div>
            </form>
        </header>
    </section>

    <section class="waiting waitingMultiple" :class="{'isApplying': showApplicationForm }"
             v-if="queue.subqueues.length > 1" aria-label="<?= Yii::t('speech', 'waiting_aria_x') ?>">
        <div v-for="subqueue in queue.subqueues" class="subqueue" :class="{'notApplyingHere': showApplicationForm !== subqueue.id}">
            <div class="nameNumber">
                <span class="name"
                     v-if="showApplicationForm !== subqueue.id && showApplicationForm !== subqueue.id + '_poo'">
                    <span class="glyphicon glyphicon-time" aria-label="<?= Yii::t('speech', 'waiting_list') ?>"></span>
                    {{ subqueue.name }}
                </span>

                <span class="number"
                     v-if="showApplicationForm !== subqueue.id && showApplicationForm !== subqueue.id + '_poo'"
                     :aria-label="numAppliedTitle(subqueue)" :title="numAppliedTitle(subqueue)">{{ subqueue.num_applied
                    }}
                </span>
            </div>

            <div v-if="subqueue.have_applied && showApplicationForm !== subqueue.id" class="appliedMe">
                <span class="label label-success" aria-label="<?= Yii::t('speech', 'applied_aria') ?>"><?= Yii::t('speech', 'applied') ?></span>
                <button type="button" class="btn btn-link btnWithdraw" @click="removeMeFromQueue($event)"
                        title="<?= Yii::t('speech', 'apply_revoke_aria') ?>" aria-label="<?= Yii::t('speech', 'apply_revoke_aria') ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span class="withdrawLabel"><?= Yii::t('speech', 'apply_revoke') ?></span>
                </button>
            </div>

            <form @submit="register($event, subqueue, false)" v-if="queue.is_open && !queue.have_applied && !queue.allow_custom_names && registerName">
                <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
            </form>

            <button class="btn btn-default btn-xs applyBtn" type="button"
                    v-if="queue.is_open && !queue.have_applied && showApplicationForm !== subqueue.id && showApplicationForm !== subqueue.id + '_poo' && !loginWarning && !(!queue.allow_custom_names && registerName)"
                    @click="onShowApplicationForm($event, subqueue, false)"
            >
                <?= Yii::t('speech', 'apply') ?>
            </button>
            <a href="<?= Html::encode($loginUrl) ?>" class="loginWarning" v-if="loginWarning">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                <?= Yii::t('speech', 'login_warning') ?>
            </a>

            <form @submit="register($event, subqueue, false)" v-if="queue.is_open && !queue.have_applied && showApplicationForm === subqueue.id">
                <label :for="'speechRegisterName' + subqueue.id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control speechRegisterName" v-model="registerName" :id="'speechRegisterName' + subqueue.id" ref="adderNameInputs">
                    <span class="input-group-btn">
                        <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
                    </span>
                </div>
            </form>

            <!-- Point of Order -->

            <form @submit="register($event, subqueue, true)" v-if="queue.is_open_poo && !queue.have_applied && !queue.allow_custom_names && registerName">
                <button class="btn btn-link btn-sm applyOpenerPoo" type="submit">
                    <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                    <?= Yii::t('speech', 'apply_poo_do') ?>
                </button>
            </form>

            <button class="btn btn-link btn-xs applyOpenerPoo" type="button"
                    v-if="queue.is_open_poo && !queue.have_applied && showApplicationForm !== subqueue.id && showApplicationForm !== (subqueue.id + '_poo') && !(!queue.allow_custom_names && registerName)"
                    :disabled="loginWarning"
                    @click="onShowApplicationForm($event, subqueue, true)"
            >
                <span class="glyphicon glyphicon-alert" aria-hidden="true"></span>
                <?= Yii::t('speech', 'apply_poo_do') ?>
            </button>

            <form @submit="register($event, subqueue, true)" v-if="!subqueue.have_applied && showApplicationForm === (subqueue.id + '_poo')">
                <label :for="'speechRegisterName' + subqueue.id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control speechRegisterName" v-model="registerName" :id="'speechRegisterName' + subqueue.id" ref="adderNameInputs">
                    <span class="input-group-btn">
                                <button class="btn btn-default" type="submit"><?= Yii::t('speech', 'apply_poo_do') ?></button>
                            </span>
                </div>
            </form>
        </div>
    </section>
</article>


<?php
$html = ob_get_clean();
?>

<script>
    __setVueComponent('speech', 'component', 'speech-user-footer-widget', {
        template: <?= json_encode($html) ?>,
        props: ['initQueue', 'csrf', 'user', 'title', 'adminUrl'],
        mixins: [SPEECH_COMMON_MIXIN],
        data() {
            return {
                registerName: this.user.name,
                defaultApplicationForm: false,
                showApplicationForm: false, // "null" is already taken by the default form
            };
        },
        beforeMount() {
            this.startPolling(false);
        },
        beforeUnmount() {
            this.stopPolling();
        }
    });
</script>
