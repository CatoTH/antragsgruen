<?php

declare(strict_types=1);

use app\models\db\{ConsultationMotionType, Consultation};
use app\models\settings\Consultation as ConsultationSettings;
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
    'has_proposed_procedure' => $item->getSettingsObj()->hasProposedProcedure,
], $consultation->motionTypes);
$serializer = \app\components\Tools::getSerializer();

$homePageView = in_array($consultation->getSettings()->startLayoutType, [
    ConsultationSettings::START_LAYOUT_AGENDA_LONG,
    ConsultationSettings::START_LAYOUT_AGENDA_HIDE_AMEND,
    ConsultationSettings::START_LAYOUT_AGENDA
]);

echo '<h1>' . Yii::t('admin', 'agenda_title') . '</h1>';

?>

<div class="content">
    <a href="<?= Html::encode(UrlHelper::homeUrl()) ?>" class="backHomeLink">
        <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
        <?= Yii::t('admin', 'agenda_back') ?>
    </a>

    <?php
    if (!$homePageView) {
        echo '<br><br><div class="alert alert-info"><p>';
        $link = Html::a(Yii::t('admin', 'index_appearance'), UrlHelper::createUrl('/admin/index/appearance'));
        echo str_replace('%LINK%', $link, Yii::t('admin', 'agenda_admin_intro'));
        echo '</p></div><br>';
    }
    ?>

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
