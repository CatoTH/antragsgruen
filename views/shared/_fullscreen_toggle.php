<?php

use app\components\{JsTools, UrlHelper};
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

$fullscreenInitData = json_encode([
    'consultation_url' => UrlHelper::createUrl(['/consultation/rest']),
    'pagination' => $consultation->getSettings()->motionPrevNextLinks,
    'init_page' => $init_page,
    'init_content_url' => $init_content_url,
]);

$layout->addJsTranslation("amend");
$layout->addJsTranslation("base");
$layout->addJsTranslation("motion");
$layout->addJsTranslation("pages");
$layout->addJsTranslation("speech");

?>
<button type="button" title="<?= Yii::t('motion', 'fullscreen') ?>" class="btn btn-link btnFullscreen"
        data-vue-element="fullscreen-projector" data-vue-initdata="<?= Html::encode($fullscreenInitData) ?>">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only"><?= Yii::t('motion', 'fullscreen') ?></span>
    </button>
    <script type="module">
    import { setSpeechUrls } from "/js/vue/speech/SpeechCommonMixins.js";
    setSpeechUrls(
        <?= json_encode(UrlHelper::createUrl(['/speech/get-queue', 'queueIds' => 'QUEUEIDS'])) ?>,
        <?= json_encode(UrlHelper::createUrl(['/speech/register', 'queueIds' => 'QUEUEIDS'])) ?>,
        <?= json_encode(UrlHelper::createUrl(['/speech/unregister', 'queueIds' => 'QUEUEIDS'])) ?>
    );
    import { FullscreenToggle } from "/js/modules/frontend/FullscreenToggle.js";
    new FullscreenToggle(document.querySelector(".btnFullscreen"));
</script>
