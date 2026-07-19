<?php

use app\components\{Tools, UrlHelper};
use app\models\api\debate\DebateState;
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
$layout->provideJwt = true;
$layout->loadCKEditor();

$initState = Tools::getSerializer()->serialize(DebateState::fromConsultation($consultation), 'json');
$pollUrl = UrlHelper::createUrl(['/rest/debate/index']);
$motionTypesUrl = UrlHelper::createUrl(['/rest/motion-type/index']);
$createMotionUrl = UrlHelper::createUrl(['/rest/motion/create']);

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
>
    <h2 class="green" id="currentDebateWidgetTitle"><?= Yii::t('debate', 'currently_debated') ?></h2>
    <div class="currentDebateWidget"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import currentDebateWidget from "/js/vue/debate/CurrentDebateWidget.js";
    import raiseSecondaryMotionForm from "/js/vue/debate/RaiseSecondaryMotionForm.js";

    const $element = $('.currentDebateInline');

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('current-debate-widget'), {
                initState: this.initState,
                pollUrl: this.pollUrl,
                motionTypesUrl: this.motionTypesUrl,
                createMotionUrl: this.createMotionUrl,
                currentUser: this.currentUser,
            });
        },
        data() {
            return {
                initState: $element.data('init-state'),
                pollUrl: $element.data('poll-url'),
                motionTypesUrl: $element.data('motion-types-url'),
                createMotionUrl: $element.data('create-motion-url'),
                currentUser: $element.data('current-user'),
            };
        }
    });

    widget.component('current-debate-widget', currentDebateWidget);
    widget.component('raise-secondary-motion-form', raiseSecondaryMotionForm);
    widget.directive('t', translateDirective);

    widget.mount('.currentDebateInline .currentDebateWidget');
</script>
