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

$this->title = 'Tagesordnung';
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Tagesordnung');

$apiModel = \app\models\api\AgendaItem::getItemsFromConsultation($consultation);
$motionTypesData = array_map(fn (ConsultationMotionType $item) => [
    'id' => $item->id,
    'title' => $item->titlePlural,
], $consultation->motionTypes);

?><h1><?= Yii::t('admin', 'con_h1') ?></h1>

<div class="content">

    <div class="agendaEditForm"
         data-antragsgruen-widget="backend/AgendaEditVue"
         data-save-agenda-url="<?= Html::encode(UrlHelper::createUrl(['/admin/index/save-agenda'])) ?>"
         data-motion-types="<?= Html::encode(json_encode($motionTypesData)) ?>"
         data-agenda="<?= Html::encode(json_encode($apiModel)) ?>">
        <div class="agendaEdit"></div>
    </div>
</div>
