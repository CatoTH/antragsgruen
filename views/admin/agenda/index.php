<?php

declare(strict_types=1);

use app\models\db\{ConsultationMotionType, Consultation};
use app\components\UrlHelper;
use yii\helpers\Html;

/**
 * @var Yii\web\View $this
 * @var Consultation $consultation
 */

/** @var \app\controllers\admin\IndexController $controller */
$controller = $this->context;
$layout = $controller->layoutParams;

$layout->addCSS('css/backend.css');
$layout->addVueTemplate('@app/views/shared/datetime_selector.vue.php');
$layout->addVueTemplate('@app/views/admin/agenda/agenda.vue.php');
$layout->loadSortable();
$layout->loadDatepicker();
$layout->loadVue();
$layout->loadVueDraggablePlus();

$this->title = Yii::t('admin', 'agenda_title');
$layout->addBreadcrumb(Yii::t('admin', 'agenda_bc'));

$apiModel = \app\models\api\AgendaItem::getItemsFromConsultation($consultation);
$motionTypesData = array_map(fn (ConsultationMotionType $item) => [
    'id' => $item->id,
    'title' => $item->titlePlural,
], $consultation->motionTypes);
$serializer = \app\components\Tools::getSerializer();

?><h1><?= Yii::t('admin', 'agenda_title') ?></h1>

<div class="content">
    <a href="<?= Html::encode(UrlHelper::createUrl('/consultation/home')) ?>" class="backHomeLink">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <?= Yii::t('admin', 'agenda_back') ?>
    </a>

    <div class="agendaEditForm"
         data-antragsgruen-widget="backend/AgendaEditVue"
         data-save-agenda-url="<?= Html::encode(UrlHelper::createUrl(['/admin/agenda/rest-index'])) ?>"
         data-motion-types="<?= Html::encode($serializer->serialize($motionTypesData, 'json')) ?>"
         data-agenda="<?= Html::encode($serializer->serialize($apiModel, 'json')) ?>">
        <div class="agendaEdit"></div>
    </div>

    <a href="<?= Html::encode(UrlHelper::createUrl('/consultation/home')) ?>" class="backHomeLink">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <?= Yii::t('admin', 'agenda_back') ?>
    </a>
</div>
