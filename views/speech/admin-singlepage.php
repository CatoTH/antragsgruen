<?php

use app\components\UrlHelper;
use app\models\db\SpeechQueue;
use yii\helpers\Html;

/**
 * @var \yii\web\View $this
 * @var SpeechQueue $queue
 */

/** @var \app\controllers\Base $controller */
$controller = $this->context;
/** @var \app\models\db\Consultation */
$consultation = $controller->consultation;
$layout       = $controller->layoutParams;
if ($queue->motion) {
    $layout->addBreadcrumb($queue->motion->getBreadcrumbTitle(), UrlHelper::createMotionUrl($queue->motion));
}
$layout->addBreadcrumb(Yii::t('speech', 'admin_bc'));

$layout->loadVue();
$layout->addVueTemplate('@app/views/speech/admin-widget.vue.php');
$layout->addVueTemplate('@app/views/speech/admin-subqueue.vue.php');

$htmls = \app\views\speech\LayoutHelper::getSidebars($consultation, $queue);
if ($htmls[0] !== '') {
    $layout->menusHtml[] = $htmls[0];
}
if ($htmls[1] !== '') {
    $layout->menusHtmlSmall[] = $htmls[1];
}

$initData = $queue->getAdminApiObject();

if ($queue->motion) {
    $this->title = str_replace('%TITLE%', $queue->motion->titlePrefix, Yii::t('speech', 'admin_title_to'));
} else {
    $this->title = Yii::t('speech', 'admin_title_plain');
}
?>
<h1><?= Html::encode($this->title) ?></h1>
<div class="manageSpeechQueue">
    <section data-antragsgruen-widget="backend/SpeechListEdit"
             data-queue="<?= Html::encode(json_encode($initData)) ?>">
        <div class="speechAdmin"></div>
    </section>
</div>
