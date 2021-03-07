<?php

/**
 * @var yii\web\View $this
 * @var \app\models\forms\ConsultationActivityFilterForm $form
 * @var \app\models\db\Motion|null $motion
 * @var \app\models\db\Amendment|null $amendment
 */

use app\components\{Tools, UrlHelper};
use yii\helpers\Html;

/** @var \app\controllers\ConsultationController $controller */
$controller = $this->context;

$consultation = UrlHelper::getCurrentConsultation();
$this->title  = Yii::t('con', 'activity_bc');

$layout = $controller->layoutParams;
if ($motion) {
    $motionUrl = UrlHelper::createMotionUrl($motion);
    $layout->addBreadcrumb($motion->getBreadcrumbTitle(), $motionUrl);
}
if ($amendment) {
    $motionUrl = UrlHelper::createMotionUrl($amendment->getMyMotion());
    $layout->addBreadcrumb($amendment->getMyMotion()->getBreadcrumbTitle(), $motionUrl);

    $amendmentUrl = UrlHelper::createAmendmentUrl($amendment);
    $layout->addBreadcrumb($amendment->titlePrefix, $amendmentUrl);
}
$layout->addBreadcrumb(Yii::t('con', 'activity_bc'));

echo '<h1>' . Html::encode(Yii::t('con', 'activity_title')) . '</h1>';
echo '<div class="content activityLogPage">';


$entries = $form->getLogEntries();
if (count($entries) === 0) {
    echo '<div class="alert alert-info">' . Yii::t('structure', 'activity_none') . '</div>';
} else {
    echo $form->getPagination('consultation/activitylog');

    echo '<ul class="list-group activityLog">';
    foreach ($entries as $entry) {
        if ($entry->formatLogEntry() === null) {
            // Deleted items; they break the pagination, but that's still better than eager-loading all dependant items
            continue;
        }

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
