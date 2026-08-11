<?php

use app\components\{DebateTools, StaticResourceTools, UrlHelper};
use yii\helpers\Html;

/**
 * @var \app\models\db\Consultation $consultation
 * @var string $init_page
 * @var string|null $init_content_url
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout = $controller->layoutParams;

// When the "Currently debated" feature is enabled, the projector offers it as a dropdown option
// (site-wide, next to motions and speaking lists) and renders the read-only user widget for it.
$debateInitData = $consultation->getSettings()->hasCurrentlyDebated
    ? DebateTools::getUserWidgetInitData($consultation)
    : null;

$fullscreenInitData = json_encode([
    'consultation_url' => UrlHelper::createUrl(['/consultation/rest']),
    'pagination' => $consultation->getSettings()->motionPrevNextLinks,
    'init_page' => $init_page,
    'init_content_url' => $init_content_url,
    'debate' => $debateInitData,
]);

$layout->addJsTranslation("amend");
$layout->addJsTranslation("base");
$layout->addJsTranslation("motion");
$layout->addJsTranslation("pages");
$layout->addJsTranslation("speech");

// The fullscreen projector may poll the speech REST endpoints, which authenticate via JWT.
$layout->provideJwt = true;

if ($debateInitData !== null) {
    $layout->addJsTranslation("debate");
    $layout->addJsTranslation("voting");
    $layout->addLiveEventSubscription('user', 'debate');
    $layout->addLiveEventSubscription('user', 'speech');
}

?>
<button type="button" title="<?= Yii::t('motion', 'fullscreen') ?>" class="btn btn-link btnFullscreen"
        data-vue-element="fullscreen-projector" data-vue-initdata="<?= Html::encode($fullscreenInitData) ?>">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only"><?= Yii::t('motion', 'fullscreen') ?></span>
    </button>
    <script type="module" crossorigin="anonymous">
    import { setSpeechUrls } from "/js/vue/speech/SpeechCommonMixins.js";
    setSpeechUrls(
        <?= json_encode(UrlHelper::createUrl(['/rest/speech/get-queue', 'queueIds' => 'QUEUEIDS'])) ?>,
        <?= json_encode(UrlHelper::createUrl(['/rest/speech/register', 'queueIds' => 'QUEUEIDS'])) ?>,
        <?= json_encode(UrlHelper::createUrl(['/rest/speech/unregister', 'queueIds' => 'QUEUEIDS'])) ?>
    );
    import { FullscreenToggle } from "/js/modules/frontend/FullscreenToggle.js";
    new FullscreenToggle(document.querySelector(".btnFullscreen"));
</script>
