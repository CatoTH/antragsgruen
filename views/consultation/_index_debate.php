<?php

use app\components\DebateTools;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$layout->addJsTranslation('debate');
$layout->addJsTranslation('motion');
$layout->addJsTranslation('speech');
$layout->provideJwt = true;
$layout->loadCKEditor();

$layout->addLiveEventSubscription('user', 'debate');
$layout->addLiveEventSubscription('user', 'speech');

// Shared with the fullscreen projector (see _fullscreen_toggle.php) so both stay in sync.
$init = DebateTools::getUserWidgetInitData($consultation);

$initState           = $init['init_state'];
$pollUrl             = $init['poll_url'];
$motionTypesUrl      = $init['motion_types_url'];
$createMotionUrl     = $init['create_motion_url'];
$speechPollUrl       = $init['speech_poll_url'];
$speechRegisterUrl   = $init['speech_register_url'];
$speechUnregisterUrl = $init['speech_unregister_url'];
$speechUser          = $init['speech_user'];
$votingConstants     = $init['voting_constants'];
$votingPollUrl       = $init['voting_poll_url'];
$votingVoteUrl       = $init['voting_vote_url'];
$votingAdminLink     = $init['voting_admin_link'];
// The initiator of a raised secondary motion is always the current user; the form has no initiator fields
$currentUserJson     = json_encode($init['current_user'], JSON_THROW_ON_ERROR);

?>
<section class="currentDebateInline currentSpeechPageWidth" aria-labelledby="currentDebateWidgetTitle"
         data-init-state="<?= Html::encode($initState) ?>"
         data-poll-url="<?= Html::encode($pollUrl) ?>"
         data-motion-types-url="<?= Html::encode($motionTypesUrl) ?>"
         data-create-motion-url="<?= Html::encode($createMotionUrl) ?>"
         data-current-user="<?= Html::encode($currentUserJson) ?>"
         data-speech-poll-url="<?= Html::encode($speechPollUrl) ?>"
         data-speech-register-url="<?= Html::encode($speechRegisterUrl) ?>"
         data-speech-unregister-url="<?= Html::encode($speechUnregisterUrl) ?>"
         data-speech-user="<?= Html::encode(json_encode($speechUser)) ?>"
         data-voting-poll-url="<?= Html::encode($votingPollUrl) ?>"
         data-voting-vote-url="<?= Html::encode($votingVoteUrl) ?>"
         data-voting-admin-link="<?= Html::encode($votingAdminLink) ?>"
>
    <h2 class="green" id="currentDebateWidgetTitle">
        <?= Yii::t('debate', 'currently_debated') ?>
        <?= $this->render('@app/views/shared/_fullscreen_toggle.php', ['init_page' => 'debate', 'init_content_url' => null]) ?>
    </h2>
    <div class="currentDebateWidget"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import currentDebateWidget from "/js/vue/debate/CurrentDebateWidget.js";
    // Adding & seconding secondary motions is disabled for now:
    // import raiseSecondaryMotionForm from "/js/vue/debate/RaiseSecondaryMotionForm.js";
    import { getSpeechCommonMixins, setSpeechUrls } from "/js/vue/speech/SpeechCommonMixins.js";
    import userInlineWidget from "/js/vue/speech/UserInlineWidget.js";
    import { getVotingCommonMixins } from "/js/vue/voting/VotingCommonMixins.js";
    import votingBlockWidget from "/js/vue/voting/VotingBlockWidget.js";
    import voteList from "/js/vue/voting/VotingList.js";

    const $element = $('.currentDebateInline');

    setSpeechUrls(
        <?= json_encode($speechPollUrl) ?>,
        <?= json_encode($speechRegisterUrl) ?>,
        <?= json_encode($speechUnregisterUrl) ?>
    );
    // The speech and voting mixins share method names (startPolling, recalcRemainingTime, …), so they
    // are applied per-component instead of globally to keep them from colliding in this shared app.
    const SPEECH_MIXINS = getSpeechCommonMixins();
    const VOTING_MIXINS = getVotingCommonMixins(<?= json_encode($votingConstants) ?>);

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('current-debate-widget'), {
                initState: this.initState,
                csrf: this.csrf,
                pollUrl: this.pollUrl,
                motionTypesUrl: this.motionTypesUrl,
                createMotionUrl: this.createMotionUrl,
                speechPollUrl: this.speechPollUrl,
                speechUser: this.speechUser,
                votingPollUrl: this.votingPollUrl,
                votingVoteUrl: this.votingVoteUrl,
                votingAdminLink: this.votingAdminLink,
                currentUser: this.currentUser,
            });
        },
        data() {
            return {
                initState: $element.data('init-state'),
                csrf: document.querySelector("meta[name='csrf-token']").getAttribute("content"),
                pollUrl: $element.data('poll-url'),
                motionTypesUrl: $element.data('motion-types-url'),
                createMotionUrl: $element.data('create-motion-url'),
                speechPollUrl: $element.data('speech-poll-url'),
                speechUser: $element.data('speech-user'),
                votingPollUrl: $element.data('voting-poll-url'),
                votingVoteUrl: $element.data('voting-vote-url'),
                votingAdminLink: $element.data('voting-admin-link'),
                currentUser: $element.data('current-user'),
            };
        }
    });

    widget.component('current-debate-widget', currentDebateWidget);
    // Adding & seconding secondary motions is disabled for now:
    // widget.component('raise-secondary-motion-form', raiseSecondaryMotionForm);
    widget.component('speech-user-inline-widget', { ...userInlineWidget, mixins: [SPEECH_MIXINS] });
    widget.component('voting-block-widget', { ...votingBlockWidget, mixins: [VOTING_MIXINS] });
    widget.component('vote-list', { ...voteList, mixins: [VOTING_MIXINS] });
    widget.directive('t', translateDirective);

    widget.mount('.currentDebateInline .currentDebateWidget');
</script>
