<?php

use app\components\{MotionSorter, UrlHelper};
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var \app\models\db\Consultation $consultation
 * @var Motion[] $resolutions
 */

echo '<section class="sectionResolutions">';
echo '<h2 class="green">' . Yii::t('con', 'resolutions') . '</h2>';

echo '<ul class="motionList motionListStd motionListWithoutAgenda resolutionList">';
$sortedResolutions = MotionSorter::getSortedIMotionsFlat($consultation, $resolutions);
foreach ($sortedResolutions as $resolution) {
    $hasPDF = $resolution->getMyMotionType()->hasPdfLayout();

    $classes = ['motion', 'motionRow' . $resolution->id];
    if ($resolution->getMyMotionType()->getSettingsObj()->cssIcon) {
        $classes[] = $resolution->getMyMotionType()->getSettingsObj()->cssIcon;
    }

    if ($resolution->status === Motion::STATUS_WITHDRAWN) {
        $classes[] = 'withdrawn';
    }
    if ($resolution->status === Motion::STATUS_MOVED) {
        $classes[] = 'moved';
    }
    if ($resolution->status === Motion::STATUS_MODIFIED) {
        $classes[] = 'modified';
    }
    echo '<li class="' . implode(' ', $classes) . '">';
    /*
    echo '<p class="date">' . Tools::formatMysqlDate($resolution->dateCreation) . '</p>' . "\n";
    */
    echo '<p class="title">' . "\n";

    $resolutionUrl = UrlHelper::createMotionUrl($resolution);
    echo '<a href="' . Html::encode($resolutionUrl) . '" class="motionLink' . $resolution->id . '">';

    echo '<span class="glyphicon glyphicon-file motionIcon"></span>';
    /*
    if (!$consultation->getSettings()->hideTitlePrefix && $resolution->titlePrefix !== '') {
        echo '<span class="motionPrefix">' . Html::encode($resolution->titlePrefix) . '</span>';
    }
    */

    $title = ($resolution->title === '' ? '-' : $resolution->title);
    echo ' <span class="motionTitle">' . Html::encode($title) . '</span>';

    echo '</a>';

    if ($hasPDF) {
        $html = '<span class="glyphicon glyphicon-download-alt"></span> PDF';
        echo Html::a($html, UrlHelper::createMotionUrl($resolution, 'pdf'), ['class' => 'pdfLink']);
    }
    echo "</p>\n";
    /*
    echo '<p class="info">';
    echo Html::encode($resolution->getInitiatorsStr());
    if ($resolution->status === Motion::STATUS_WITHDRAWN) {
        echo ' <span class="status">(' . Html::encode($resolution->getStatusNames()[$resolution->status]) . ')</span>';
    }
    echo '</p>';
    echo "<span class='clearfix'></span>\n";
    */
    echo '</li>';
}
echo '</ul>';
echo '</section>';
