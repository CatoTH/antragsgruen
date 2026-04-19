<?php

use app\components\{JsTools, UrlHelper};
use yii\helpers\Html;

/**
 * @var \app\models\db\Consultation $consultation
 * @var string $init_page
 * @var string|null $init_content_url
 */

$fullscreenInitData = json_encode([
    'consultation_url' => UrlHelper::createUrl(['/consultation/rest']),
    'pagination' => $consultation->getSettings()->motionPrevNextLinks,
    'init_page' => $init_page,
    'init_content_url' => $init_content_url,
]);
$jsTranslations = json_encode([
    "amend" => JsTools::getTranslations($consultation, "amend"),
    "base" => JsTools::getTranslations($consultation, "base"),
    "motion" => JsTools::getTranslations($consultation, "motion"),
    "pages" => JsTools::getTranslations($consultation, "pages"),
    "speech" => JsTools::getTranslations($consultation, "speech"),
]);

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
    new FullscreenToggle(document.querySelector(".btnFullscreen"), <?= $jsTranslations ?>);
</script>
