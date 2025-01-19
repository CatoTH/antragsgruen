<?php

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
$layout->addVueTemplate('@app/views/admin/index/agenda.vue.php');
$layout->loadSortable();
$layout->loadVue();
$layout->loadVueDraggable();

$this->title = 'Tagesordnung';
$layout->addBreadcrumb(Yii::t('admin', 'bread_settings'), UrlHelper::createUrl('admin/index'));
$layout->addBreadcrumb('Tagesordnung');

?><h1><?= Yii::t('admin', 'con_h1') ?></h1>

<div class="content">

    <div class="agendaEditForm"
         data-antragsgruen-widget="backend/AgendEditVue"
         data-agenda="<?= Html::encode(json_encode([
             ["id" => 1, "title" => "TOP 1"],
             ["id" => 2, "title" => "TOP 2"],
         ])) ?>">
        <div class="agendaEdit"></div>
    </div>
</div>
