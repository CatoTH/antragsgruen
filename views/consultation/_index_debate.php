<?php

use app\components\{Tools, UrlHelper};
use app\models\api\debate\DebateState;
use app\models\api\SpeechUser;
use app\models\db\User;
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

    const $element = $('.currentDebateInline');

    setSpeechUrls(
        <?= json_encode($speechPollUrl) ?>,
        <?= json_encode($speechRegisterUrl) ?>,
        <?= json_encode($speechUnregisterUrl) ?>
    );
    const SPEECH_MIXINS = getSpeechCommonMixins();

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
                currentUser: $element.data('current-user'),
            };
        }
    });

    widget.mixin(SPEECH_MIXINS);
    widget.component('current-debate-widget', currentDebateWidget);
    widget.component('raise-secondary-motion-form', raiseSecondaryMotionForm);
    widget.component('speech-user-inline-widget', userInlineWidget);
    widget.directive('t', translateDirective);

    widget.mount('.currentDebateInline .currentDebateWidget');
</script>
