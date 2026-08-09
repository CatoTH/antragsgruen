<?php

use app\components\{Tools, UrlHelper};
use app\models\api\debate\DebateState;
use app\models\api\SpeechUser;
use app\models\db\User;
use app\models\settings\Privileges;
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

$user = User::getCurrentUser();
$cookieUser = ($user ? null : \app\components\CookieUser::getFromCookieOrCache());

$initState = Tools::getSerializer()->serialize(DebateState::fromConsultation($consultation), 'json');
$pollUrl            = UrlHelper::createUrl(['/rest/debate/index']);
$motionTypesUrl      = UrlHelper::createUrl(['/rest/motion-type/index']);
$createMotionUrl     = UrlHelper::createUrl(['/rest/motion/create']);
$speechPollUrl       = UrlHelper::createUrl(['/rest/speech/get-queue', 'queueIds' => 'QUEUEIDS']);
$speechRegisterUrl   = UrlHelper::createUrl(['/rest/speech/register', 'queueId' => 'QUEUEID']);
$speechUnregisterUrl = UrlHelper::createUrl(['/rest/speech/unregister', 'queueId' => 'QUEUEID']);
$speechUser          = new SpeechUser($user, $cookieUser);

// Voting: the embedded widget reuses the existing session-based /voting endpoints, which require a
// logged-in user. Anonymous visitors therefore get empty URLs and no voting is embedded (same as the
// standalone homepage voting widget). The block shown is the one referenced by the debate's votingBlockId.
$votingConstants = include(__DIR__ . '/../voting/_constants.php');
if ($user) {
    $votingPollUrl   = UrlHelper::createUrl(['/voting/get-open-voting-blocks', 'assignedToMotionId' => '', 'showAllOpen' => 1]);
    $votingVoteUrl   = UrlHelper::createUrl(['/voting/post-vote', 'votingBlockId' => 'VOTINGBLOCKID', 'assignedToMotionId' => '', 'showAllOpen' => 1]);
    $votingAdminLink = $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null)
        ? UrlHelper::createUrl(['/consultation/admin-votings'])
        : '';
} else {
    $votingPollUrl   = '';
    $votingVoteUrl   = '';
    $votingAdminLink = '';
}

// The initiator of a raised secondary motion is always the current user; the form has no initiator fields
$currentUser = User::getCurrentUser();
$currentUserJson = json_encode($currentUser ? [
    'name' => $currentUser->name,
    'organization' => $currentUser->organization,
    'email' => $currentUser->email,
] : null, JSON_THROW_ON_ERROR);

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
    <h2 class="green" id="currentDebateWidgetTitle"><?= Yii::t('debate', 'currently_debated') ?></h2>
    <div class="currentDebateWidget"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import currentDebateWidget from "/js/vue/debate/CurrentDebateWidget.js";
    import raiseSecondaryMotionForm from "/js/vue/debate/RaiseSecondaryMotionForm.js";
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
    widget.component('raise-secondary-motion-form', raiseSecondaryMotionForm);
    widget.component('speech-user-inline-widget', { ...userInlineWidget, mixins: [SPEECH_MIXINS] });
    widget.component('voting-block-widget', { ...votingBlockWidget, mixins: [VOTING_MIXINS] });
    widget.component('vote-list', { ...voteList, mixins: [VOTING_MIXINS] });
    widget.directive('t', translateDirective);

    widget.mount('.currentDebateInline .currentDebateWidget');
</script>
