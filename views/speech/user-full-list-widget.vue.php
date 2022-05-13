<?php

use app\components\UrlHelper;
use yii\helpers\Html;

$loginUrl = UrlHelper::createUrl(['user/login', 'backUrl' => Yii::$app->request->url]);

ob_start();
?>

<article class="speechUser">
    <div class="content">
        <div class="activeSpeaker">
            <span class="glyphicon glyphicon-comment leftIcon" aria-hidden="true"></span>
            <span v-if="activeSpeaker" class="existing">
                <?= Yii::t('speech', 'current') ?>:
            </span>
            <div v-if="activeSpeaker" class="name">{{ activeSpeaker.name }}</div>
            <span v-if="!activeSpeaker" class="notExisting">
                <?= Yii::t('speech', 'current_nobody') ?>
            </span>
        </div>
        <div class="remainingTime" v-if="activeSpeaker && hasSpeakingTime && remainingSpeakingTime !== null">
            <?= Yii::t('speech', 'remaining_time') ?>:<br>
            <span v-if="remainingSpeakingTime >= 0" class="time">{{ formattedRemainingTime }}</span>
            <span v-if="remainingSpeakingTime < 0" class="over"><?= Yii::t('speech', 'remaining_time_over') ?></span>
        </div>
        <div v-if="upcomingSpeakers.length > 0" class="upcomingSpeaker">
            <span class="glyphicon glyphicon-user" aria-hidden="true"></span>
            <?= Yii::t('speech', 'next_speaker') ?>:
            <ul class="upcomingSpeakerList">
                <li v-for="speaker in upcomingSpeakers">
                    <span class="name">{{ speaker.name }}</span><!-- Fight unwanted whitespace
                --><span class="label label-success" v-if="isMe(speaker)"><?= Yii::t('speech', 'you') ?></span><!-- Fight unwanted whitespace
                --><button type="button" class="btn btn-link btnWithdraw" v-if="isMe(speaker)"
                            @click="removeMeFromQueue($event)"
                            title="<?= Yii::t('speech', 'apply_revoke_aria') ?>"
                            aria-label="<?= Yii::t('speech', 'apply_revoke_aria') ?>">
                        <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                        <span class="withdrawLabel"><?= Yii::t('speech', 'apply_revoke') ?></span>
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <h2 class="green"><?= Yii::t('speech', 'waiting_list') ?>: {{ queue.subqueues[0].num_applied }}</h2>

    <div class="content">
    <section class="waiting waitingSingle" v-if="queue.subqueues.length === 1" aria-label="<?= Yii::t('speech', 'waiting_aria_1') ?>">
        <header>
            <ol class="nameList" v-if="queue.subqueues[0].applied && queue.subqueues[0].applied.length > 0" title="<?= Yii::t('speech', 'persons_waiting') ?>">
                <li v-for="applied in queue.subqueues[0].applied">
                    <span class="glyphicon glyphicon-time leftIcon" aria-hidden="true"></span>
                    {{ applied.name }}
                </li>
            </ol>

            <div v-if="queue.subqueues[0].have_applied" class="appliedMe">
                <span class="label label-success"><?= Yii::t('speech', 'applied') ?></span>
                <button type="button" class="btn btn-link btnWithdraw" @click="removeMeFromQueue($event)"
                        title="<?= Yii::t('speech', 'apply_revoke_aria') ?>" aria-label="<?= Yii::t('speech', 'apply_revoke_aria') ?>">
                    <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                    <span class="withdrawLabel"><?= Yii::t('speech', 'apply_revoke') ?></span>
                </button>
            </div>
        </header>

        <div class="apply">
            <div class="notPossible" v-if="!queue.is_open">
                <?= Yii::t('speech', 'apply_closed') ?>
            </div>
            <button class="btn btn-primary applyOpener" type="button"
                    v-if="queue.is_open && !queue.have_applied && showApplicationForm !== queue.subqueues[0].id"
                    :disabled="loginWarning"
                    @click="onShowApplicationForm($event, queue.subqueues[0])"
            >
                <?= Yii::t('speech', 'apply') ?>
            </button>
            <a href="<?= Html::encode($loginUrl) ?>" class="loginWarning" v-if="loginWarning">
                <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                <?= Yii::t('speech', 'login_warning') ?>
            </a>

            <form @submit="register($event, queue.subqueues)" v-if="queue.is_open && !queue.subqueues[0].have_applied && showApplicationForm === queue.subqueues[0].id">
                <label :for="'speechRegisterName' + queue.subqueues[0].id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                <div class="input-group">
                    <input type="text" class="form-control" v-model="registerName" :id="'speechRegisterName' + queue.subqueues[0].id" ref="adderNameInput">
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
                    </span>
                </div>
            </form>
        </div>
    </section>

    <section class="waiting waitingMultiple" v-if="queue.subqueues.length > 1" aria-label="<?= Yii::t('speech', 'waiting_aria_x') ?>">
        <header>
            <span class="glyphicon glyphicon-time leftIcon" aria-hidden="true"></span>
            <?= Yii::t('speech', 'waiting_list_x') ?>
        </header>
        <div class="waitingSubqueues">
            <div v-for="subqueue in queue.subqueues" class="subqueue">
                <div class="name">
                    {{ subqueue.name }}:
                </div>
                <div class="applied">
                    <button class="btn btn-default btn-xs" type="button"
                            v-if="queue.is_open && !queue.have_applied && showApplicationForm !== subqueue.id"
                            :disabled="loginWarning"
                            @click="onShowApplicationForm($event, subqueue)"
                    >
                        <?= Yii::t('speech', 'apply') ?>
                    </button>
                    <a href="<?= Html::encode($loginUrl) ?>" class="loginWarning" v-if="loginWarning">
                        <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
                        <?= Yii::t('speech', 'login_warning') ?>
                    </a>

                    <span class="number" v-if="showApplicationForm !== subqueue.id" title="<?= Yii::t('speech', 'persons_waiting') ?>">
                        <span class="glyphicon glyphicon-time" aria-label="<?= Yii::t('speech', 'persons_waiting') ?>"></span>
                        {{ subqueue.num_applied }}
                    </span>
                    <ol class="nameList" v-if="subqueue.applied && subqueue.applied.length > 0 && showApplicationForm !== subqueue.id" title="<?= Yii::t('speech', 'persons_waiting') ?>">
                        <li v-for="applied in subqueue.applied">{{ applied.name }}</li>
                    </ol>

                    <div v-if="subqueue.have_applied" class="appliedMe">
                        <span class="label label-success"><?= Yii::t('speech', 'applied') ?></span>
                        <button type="button" class="btn btn-link btnWithdraw" @click="removeMeFromQueue($event)"
                                title="<?= Yii::t('speech', 'apply_revoke_aria') ?>" aria-label="<?= Yii::t('speech', 'apply_revoke_aria') ?>">
                            <span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
                            <span class="withdrawLabel"><?= Yii::t('speech', 'apply_revoke') ?></span>
                        </button>
                    </div>

                    <form @submit="register($event, subqueue)" v-if="queue.is_open && !queue.have_applied && showApplicationForm === subqueue.id">
                        <label :for="'speechRegisterName' + subqueue.id" class="sr-only"><?= Yii::t('speech', 'apply_name') ?></label>
                        <div class="input-group">
                            <input type="text" class="form-control" v-model="registerName" :id="'speechRegisterName' + subqueue.id" ref="adderNameInputs">
                            <span class="input-group-btn">
                                <button class="btn btn-primary" type="submit"><?= Yii::t('speech', 'apply_do') ?></button>
                            </span>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="notPossible" v-if="!queue.is_open">
            <?= Yii::t('speech', 'apply_closed') ?>
        </div>
    </section>
    </div>
</article>


<?php
$html          = ob_get_clean();
?>

<script>
    Vue.component('speech-user-full-list-widget', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf', 'user', 'title'],
        mixins: [SPEECH_COMMON_MIXIN],
        data() {
            return {
                registerName: this.user.name,
                showApplicationForm: null // null = default form
            };
        },
        created() {
            this.startPolling(false);
        },
        beforeDestroy() {
            this.stopPolling();
        }
    });
</script>
