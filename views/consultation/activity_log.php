<?php

/**
 * @var yii\web\View $this
 * @var \app\models\forms\ConsultationActivityFilterForm $form
 */

use app\components\Tools;
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = \app\components\UrlHelper::getCurrentConsultation();
$this->title  = \Yii::t('con', 'activity_bc');

$layout = $controller->layoutParams;
$layout->addBreadcrumb(\Yii::t('con', 'activity_bc'));

echo '<h1>' . Html::encode(\Yii::t('con', 'activity_title')) . '</h1>';
echo '<div class="content activityLogPage">';


$entries = $form->getLogEntries();
if (count($entries) == 0) {
    echo '<div class="alert alert-info">' . \Yii::t('structure', 'activity_none') . '</div>';
} else {
    echo $form->getPagination('consultation/activitylog');

    echo '<ul class="list-group activityLog">';
    foreach ($entries as $entry) {
        $link = $entry->getLink();
        if ($link) {
            echo '<a href="' . Html::encode($link) . '" class="list-group-item">';
        } else {
            echo '<li class="list-group-item">';
        }
        echo '<div class="date" title="' . Html::encode(Tools::formatMysqlDateTime($entry->actionTime)) . '">';
        echo $entry->getTimeAgoFormatted() . '</div>';

        if ($motion = $entry->getMotion()) {
            echo '<div class="motion">' . $motion->getEncodedTitleWithPrefix() . '</div>';
        }

        echo '<div class="description">' . $entry->formatLogEntry() . '</div>';

        if ($link) {
            echo '</a>';
        } else {
            echo '</li>';
        }
    }
    echo '</ul>';

    echo $form->getPagination('consultation/activitylog');
}

echo '</div>';
