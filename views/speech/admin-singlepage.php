<?php

use app\components\UrlHelper;
use app\models\api\SpeechQueue as SpeechQueueApi;
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
if ($queue->motion) {
    $layout->addBreadcrumb($queue->motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($queue->motion));
} elseif ($queue->agendaItem) {
    $layout->addBreadcrumb(Yii::t('admin', 'index_site_agenda'), UrlHelper::createUrl(['/admin/agenda/index']));
} else {
    $layout->addBreadcrumb(Yii::t('speech', 'speaking_bc'), UrlHelper::createUrl(['/consultation/speech']));
}
$layout->addBreadcrumb(Yii::t('speech', 'admin_bc'));

$layout->provideJwt = true;
$layout->addLiveEventSubscription('admin', 'speech');

$layout->addJsTranslation('speech');

$htmls = \app\views\speech\LayoutHelper::getSidebars($consultation, $queue);
if ($htmls[0] !== '') {
    $layout->menusHtml[] = $htmls[0];
}
if ($htmls[1] !== '') {
    $layout->menusHtmlSmall[] = $htmls[1];
}

$initData = SpeechQueueApi::fromEntity($queue)->getAdminApiObject();

if ($queue->motion) {
    $this->title = str_replace('%TITLE%', $queue->motion->getFormattedTitlePrefix(), Yii::t('speech', 'admin_title_to'));
} elseif ($queue->agendaItem) {
    $this->title = str_replace('%TITLE%', $queue->agendaItem->title, Yii::t('speech', 'admin_title_to'));
} else {
    $this->title = Yii::t('speech', 'admin_title_plain');
}

$componentAdminLink = UrlHelper::createUrl('admin/index/appearance') . '#hasSpeechLists';
$setStatusUrl      = UrlHelper::createUrl(['/speech/post-queue-settings', 'queueId' => 'QUEUEID']);
$itemPerformOpUrl  = UrlHelper::createUrl(['/speech/post-item-operation', 'queueId' => 'QUEUEID', 'itemId' => 'ITEMID', 'op' => 'OPERATION']);
$createItemUrl     = UrlHelper::createUrl(['/speech/admin-create-item', 'queueId' => 'QUEUEID']);
$resetQueueUrl     = UrlHelper::createUrl(['/speech/admin-queue-reset', 'queueId' => 'QUEUEID']);
$randomizeQueueUrl = UrlHelper::createUrl(['/speech/admin-queue-randomize', 'queueId' => 'QUEUEID']);
$pollUrl           = UrlHelper::createUrl(['/speech/get-queue-admin', 'queueId' => 'QUEUEID']);

?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="manageSpeechQueue">
    <section class="manageSpeechQueueWidget"
             data-component-admin-link="<?= Html::encode($componentAdminLink) ?>"
             data-poll-url="<?= Html::encode($pollUrl) ?>"
             data-item-perform-operation-url="<?= Html::encode($itemPerformOpUrl) ?>"
             data-randomize-queue-url="<?= Html::encode($randomizeQueueUrl) ?>"
             data-reset-queue-url="<?= Html::encode($resetQueueUrl) ?>"
             data-create-item-url="<?= Html::encode($createItemUrl) ?>"
             data-set-status-url="<?= Html::encode($setStatusUrl) ?>"
             data-queue="<?= Html::encode(json_encode($initData)) ?>">
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
                pollUrl: this.pollUrl,
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
            pollUrl: element.getAttribute("data-poll-url"),
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
