<?php

use app\components\{LiveDataChannels, Tools, UrlHelper};
use app\models\api\speech\SpeechQueueAdmin;
use app\models\db\SpeechQueue;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var SpeechQueue $queue
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
$layout->bodyCssClasses[] = 'manageSpeechPage';
if ($queue->motion) {
    $layout->addBreadcrumb($queue->motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($queue->motion));
} elseif ($queue->amendment) {
    $amendedMotion = $queue->amendment->getMyMotion();
    if ($amendedMotion) {
        $layout->addBreadcrumb($amendedMotion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($amendedMotion));
    }
    $layout->addBreadcrumb(
        $queue->amendment->getFormattedTitlePrefix() ?? Yii::t('amend', 'amendment'),
        UrlHelper::createAmendmentUrl($queue->amendment)
    );
} elseif ($queue->agendaItem) {
    $layout->addBreadcrumb(Yii::t('admin', 'index_site_agenda'), UrlHelper::createUrl(['/admin/agenda/index']));
} else {
    $layout->addBreadcrumb(Yii::t('speech', 'speaking_bc'), UrlHelper::createUrl(['/consultation/speech']));
}
$layout->addBreadcrumb(Yii::t('speech', 'admin_bc'));

$layout->provideJwt = true;
$layout->addLiveDataChannel(LiveDataChannels::ROLE_ADMIN, LiveDataChannels::CHANNEL_SPEECH);

$layout->addJsTranslation('speech');

$htmls = \app\views\speech\LayoutHelper::getSidebars($consultation, $queue);
if ($htmls[0] !== '') {
    $layout->menusHtml[] = $htmls[0];
}
if ($htmls[1] !== '') {
    $layout->menusHtmlSmall[] = $htmls[1];
}

$initData = Tools::getSerializer()->serialize(SpeechQueueAdmin::fromEntity($queue), 'json');

if ($queue->motion) {
    $this->title = str_replace('%TITLE%', $queue->motion->getFormattedTitlePrefix(), Yii::t('speech', 'admin_title_to'));
} elseif ($queue->amendment) {
    $this->title = str_replace('%TITLE%', (string)$queue->amendment->getFormattedTitlePrefix(), Yii::t('speech', 'admin_title_to'));
} elseif ($queue->agendaItem) {
    $this->title = str_replace('%TITLE%', $queue->agendaItem->title, Yii::t('speech', 'admin_title_to'));
} else {
    $this->title = Yii::t('speech', 'admin_title_plain');
}

$componentAdminLink = UrlHelper::createUrl('/admin/index/appearance') . '#hasSpeechLists';
$setStatusUrl       = UrlHelper::createUrl(['/rest/speech/post-queue-settings', 'queueId' => 'QUEUEID']);
$itemPerformOpUrl   = UrlHelper::createUrl(['/rest/speech/post-item-operation', 'queueId' => 'QUEUEID', 'itemId' => 'ITEMID', 'op' => 'OPERATION']);
$createItemUrl      = UrlHelper::createUrl(['/rest/speech/admin-create-item', 'queueId' => 'QUEUEID']);
$resetQueueUrl      = UrlHelper::createUrl(['/rest/speech/admin-queue-reset', 'queueId' => 'QUEUEID']);
$randomizeQueueUrl  = UrlHelper::createUrl(['/rest/speech/admin-queue-randomize', 'queueId' => 'QUEUEID']);

?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="manageSpeechQueue">
    <section class="manageSpeechQueueWidget"
             data-component-admin-link="<?= Html::encode($componentAdminLink) ?>"
             data-item-perform-operation-url="<?= Html::encode($itemPerformOpUrl) ?>"
             data-randomize-queue-url="<?= Html::encode($randomizeQueueUrl) ?>"
             data-reset-queue-url="<?= Html::encode($resetQueueUrl) ?>"
             data-create-item-url="<?= Html::encode($createItemUrl) ?>"
             data-set-status-url="<?= Html::encode($setStatusUrl) ?>"
             data-queue="<?= Html::encode($initData) ?>">
        <div class="speechAdmin"></div>
    </section>
</div>

<script type="module" crossorigin="anonymous">
    import { createApp, h, resolveComponent } from '/npm/vue.runtime.esm-browser.prod.js';
    import translateDirective from "/js/vue/Translate.vue.js";
    import AdminWidgetComponent from "/js/vue/speech/AdminWidget.js";
    import AdminSubqueueComponent from "/js/vue/speech/AdminSubqueue.js";

    const element = document.querySelector(".manageSpeechQueueWidget");

    /** @type {import('vue').App} */
    const widget = createApp({
        render() {
            return h(resolveComponent('speech-admin-widget'), {
                initQueue: this.queue,
                csrf: this.csrf,
                componentAdminLink: this.componentAdminLink,
                itemPerformOperationUrl: this.itemPerformOperationUrl,
                randomizeQueueUrl: this.randomizeQueueUrl,
                resetQueueUrl: this.resetQueueUrl,
                createItemUrl: this.createItemUrl,
                setStatusUrl: this.setStatusUrl,
            });
        },
        data() { return {
            queue: JSON.parse(element.getAttribute("data-queue")),
            csrf: document.querySelector("meta[name='csrf-token']").getAttribute("content"),
            componentAdminLink: element.getAttribute("data-component-admin-link"),
            itemPerformOperationUrl: element.getAttribute("data-item-perform-operation-url"),
            randomizeQueueUrl: element.getAttribute("data-randomize-queue-url"),
            resetQueueUrl: element.getAttribute("data-reset-queue-url"),
            createItemUrl: element.getAttribute("data-create-item-url"),
            setStatusUrl: element.getAttribute("data-set-status-url"),
        } }
    });

    widget.component('speech-admin-subqueue', AdminSubqueueComponent)
    widget.component('speech-admin-widget', AdminWidgetComponent);

    widget.directive('t', translateDirective);

    widget.mount(".manageSpeechQueueWidget .speechAdmin");
</script>
