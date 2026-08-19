<?php

use app\components\{DebateTools, LiveDataChannels, StaticResourceTools, UrlHelper};
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
// The state is deliberately omitted (includeState: false): the projector may be opened long after the
// page was rendered, so it loads the current debate state from the backend on open rather than showing
// a stale page-load snapshot.
$debateInitData = $consultation->getSettings()->hasCurrentlyDebated
    ? DebateTools::getUserWidgetInitData($consultation, false)
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

// The projector can show speaking lists on any page it is available on, not only when the debate
// widget is present - so the speech channel is always needed.
$layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_SPEECH);

if ($debateInitData !== null) {
    $layout->addJsTranslation("debate");
    $layout->addJsTranslation("voting");
    $layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_DEBATE);
}

?>
<button type="button" title="<?= Yii::t('motion', 'fullscreen') ?>" class="btn btn-link btnFullscreen"
        data-vue-element="fullscreen-projector" data-vue-initdata="<?= Html::encode($fullscreenInitData) ?>">
        <span class="glyphicon glyphicon-fullscreen" aria-hidden="true"></span>
        <span class="sr-only"><?= Yii::t('motion', 'fullscreen') ?></span>
    </button>
    <script type="module" crossorigin="anonymous">
    import { setSpeechActionUrls } from "/js/vue/speech/SpeechCommonMixins.js";
    setSpeechActionUrls(
        <?= json_encode(UrlHelper::createUrl(['/rest/speech/register', 'queueId' => 'QUEUEID'])) ?>,
        <?= json_encode(UrlHelper::createUrl(['/rest/speech/unregister', 'queueId' => 'QUEUEID'])) ?>
    );
    import { FullscreenToggle } from "/js/modules/frontend/FullscreenToggle.js";
    new FullscreenToggle(document.querySelector(".btnFullscreen"));
</script>
