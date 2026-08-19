<?php

use app\components\{LiveDataChannels, Tools, UrlHelper};
use app\models\api\debate\DebateState;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$layout->addJsTranslation('debate');
$layout->addJsTranslation('speech');
$layout->addLiveDataChannel(LiveDataChannels::ROLE_ADMIN, LiveDataChannels::CHANNEL_SPEECH);
$layout->addLiveDataChannel(LiveDataChannels::ROLE_USER, LiveDataChannels::CHANNEL_SPEECH);
$layout->provideJwt = true;

$initState = Tools::getSerializer()->serialize(DebateState::fromConsultation($consultation), 'json');
$debateUrl = UrlHelper::createUrl(['/rest/debate/index']);
$selectableUrl = UrlHelper::createUrl(['/rest/debate/selectable']);
$speechQueueUrl = UrlHelper::createUrl(['/rest/debate/speech-queue']);
$votingUrl = UrlHelper::createUrl(['/rest/debate/voting']);

// Endpoints of the embedded speech-admin widget (JWT REST API, shared with the standalone speech admin page).
// The "QUEUEID" placeholder is substituted with the concrete queue id inside the Vue widget.
$speechComponentAdminLink = UrlHelper::createUrl('admin/index/appearance') . '#hasSpeechLists';
$speechSetStatusUrl      = UrlHelper::createUrl(['/rest/speech/post-queue-settings', 'queueId' => 'QUEUEID']);
$speechItemPerformOpUrl  = UrlHelper::createUrl(['/rest/speech/post-item-operation', 'queueId' => 'QUEUEID', 'itemId' => 'ITEMID', 'op' => 'OPERATION']);
$speechCreateItemUrl     = UrlHelper::createUrl(['/rest/speech/admin-create-item', 'queueId' => 'QUEUEID']);
$speechResetQueueUrl     = UrlHelper::createUrl(['/rest/speech/admin-queue-reset', 'queueId' => 'QUEUEID']);
$speechRandomizeQueueUrl = UrlHelper::createUrl(['/rest/speech/admin-queue-randomize', 'queueId' => 'QUEUEID']);

?>
<section class="currentDebateAdmin currentSpeechPageWidth" aria-labelledby="currentDebateAdminTitle"
         data-init-state="<?= Html::encode($initState) ?>"
         data-debate-url="<?= Html::encode($debateUrl) ?>"
         data-selectable-url="<?= Html::encode($selectableUrl) ?>"
         data-speech-queue-url="<?= Html::encode($speechQueueUrl) ?>"
         data-voting-url="<?= Html::encode($votingUrl) ?>"
         data-speech-component-admin-link="<?= Html::encode($speechComponentAdminLink) ?>"
         data-speech-item-perform-operation-url="<?= Html::encode($speechItemPerformOpUrl) ?>"
         data-speech-randomize-queue-url="<?= Html::encode($speechRandomizeQueueUrl) ?>"
         data-speech-reset-queue-url="<?= Html::encode($speechResetQueueUrl) ?>"
         data-speech-create-item-url="<?= Html::encode($speechCreateItemUrl) ?>"
         data-speech-set-status-url="<?= Html::encode($speechSetStatusUrl) ?>"
>
    <h2 class="green" id="currentDebateAdminTitle">
        <?= Yii::t('debate', 'admin_title') ?>
        <?= $this->render('@app/views/shared/_fullscreen_toggle.php', ['init_page' => 'debate', 'init_content_url' => null]) ?>
    </h2>
    <div class="currentDebateAdminWidget"></div>
</section>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import debateAdminWidget from "/js/vue/debate/DebateAdminWidget.js";
    import AdminWidgetComponent from "/js/vue/speech/AdminWidget.js";
    import AdminSubqueueComponent from "/js/vue/speech/AdminSubqueue.js";

    const $element = $('.currentDebateAdmin');

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('debate-admin-widget'), {
                initState: this.initState,
                debateUrl: this.debateUrl,
                selectableUrl: this.selectableUrl,
                speechQueueUrl: this.speechQueueUrl,
                votingUrl: this.votingUrl,
                csrf: this.csrf,
                speechComponentAdminLink: this.speechComponentAdminLink,
                speechItemPerformOperationUrl: this.speechItemPerformOperationUrl,
                speechRandomizeQueueUrl: this.speechRandomizeQueueUrl,
                speechResetQueueUrl: this.speechResetQueueUrl,
                speechCreateItemUrl: this.speechCreateItemUrl,
                speechSetStatusUrl: this.speechSetStatusUrl,
            });
        },
        data() {
            return {
                initState: $element.data('init-state'),
                debateUrl: $element.data('debate-url'),
                selectableUrl: $element.data('selectable-url'),
                speechQueueUrl: $element.data('speech-queue-url'),
                votingUrl: $element.data('voting-url'),
                csrf: document.querySelector("meta[name='csrf-token']").getAttribute("content"),
                speechComponentAdminLink: $element.data('speech-component-admin-link'),
                speechItemPerformOperationUrl: $element.data('speech-item-perform-operation-url'),
                speechRandomizeQueueUrl: $element.data('speech-randomize-queue-url'),
                speechResetQueueUrl: $element.data('speech-reset-queue-url'),
                speechCreateItemUrl: $element.data('speech-create-item-url'),
                speechSetStatusUrl: $element.data('speech-set-status-url'),
            };
        }
    });

    widget.component('speech-admin-subqueue', AdminSubqueueComponent);
    widget.component('speech-admin-widget', AdminWidgetComponent);
    widget.component('debate-admin-widget', debateAdminWidget);
    widget.directive('t', translateDirective);

    widget.mount('.currentDebateAdmin .currentDebateAdminWidget');
</script>
