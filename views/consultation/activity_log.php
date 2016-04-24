<?php

/**
 * @var yii\web\View $this
 * @var \app\models\forms\ConsultationActivityFilterForm $form
 */

use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = \app\components\UrlHelper::getCurrentConsultation();
$this->title  = \Yii::t('con', 'activity_bc');

$layout = $controller->layoutParams;
$layout->addBreadcrumb(\Yii::t('con', 'activity_bc'));

echo '<h1>' . Html::encode(\Yii::t('con', 'activity_title')) . '</h1>';
echo '<div class="content activityLogPage">';

$entries = $form->getLogEntries(0, 20);

echo '<ul class="list-group activityLog">';
foreach ($entries as $entry) {
    echo '<li class="list-group-item">';
    echo '<div class="date">' . $entry->actionTime . '</div>';
    echo $entry->formatLogEntry();
    echo '</li>';
}
echo '</ul>';

echo '</div>';
