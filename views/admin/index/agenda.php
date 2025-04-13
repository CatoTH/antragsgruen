<?php

use app\models\db\ConsultationMotionType;
use app\components\{HTMLTools, UrlHelper};
use app\models\db\Consultation;
use app\models\settings\Consultation as ConsultationSettings;
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
$layout->addVueTemplate('@app/views/admin/index/agenda.vue.php');
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

?><h1><?= Yii::t('admin', 'con_h1') ?></h1>

<div class="content">

    <a href="<?= Html::encode(UrlHelper::createUrl('/consultation/home')) ?>">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <?= Yii::t('admin', 'agenda_back') ?>
    </a>

    <div class="agendaEditForm"
         data-antragsgruen-widget="backend/AgendaEditVue"
         data-save-agenda-url="<?= Html::encode(UrlHelper::createUrl(['/admin/index/save-agenda'])) ?>"
         data-motion-types="<?= Html::encode(json_encode($motionTypesData)) ?>"
         data-agenda="<?= Html::encode(json_encode($apiModel)) ?>">
        <div class="agendaEdit"></div>
    </div>

    <a href="<?= Html::encode(UrlHelper::createUrl('/consultation/home')) ?>">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <?= Yii::t('admin', 'agenda_back') ?>
    </a>
</div>
