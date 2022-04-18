<?php

ob_start();
?>

<article class="speechLists currentSpeechPageWidth">
    <div class="content" v-if="queue">
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

    <div v-if="queue">
        <h2 class="green"><?= Yii::t('speech', 'waiting_list') ?>: {{ queue.subqueues[0].num_applied }}</h2>

        <section class="waiting waitingSingle" v-if="queue.subqueues.length === 1" aria-label="<?= Yii::t('speech', 'waiting_aria_1') ?>">
            <ol class="nameList" v-if="queue.subqueues[0].applied && queue.subqueues[0].applied.length > 0" title="<?= Yii::t('speech', 'persons_waiting') ?>">
                <li v-for="applied in queue.subqueues[0].applied">
                    <span class="glyphicon glyphicon-time leftIcon" aria-hidden="true"></span>
                    {{ applied.name }}
                </li>
            </ol>
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
                </div>
            </div>
        </section>
    </div>
</article>


<?php
$html = ob_get_clean();
?>

<script>
    Vue.component('fullscreen-speech', {
        template: <?= json_encode($html) ?>,
        props: ['queue', 'csrf', 'user', 'title'],
        mixins: [SPEECH_COMMON_MIXIN],
        created() {
            this.startPolling();
        },
        beforeDestroy() {
            this.stopPolling();
        }
    });
</script>
