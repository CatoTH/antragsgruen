<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\plugins\memberPetitions\Tools;
use yii\helpers\Html;

/**
 * @var Motion[] $motions
 * @var string $bold
 */

if (count($motions) === 0) {
    echo \Yii::t('memberpetitions', 'none');
    return;
}

echo '<ul class="motionList motionListPetitions">';
foreach ($motions as $motion) {
    $status = $motion->getFormattedStatus();

    echo '<li class="motion motionRow' . $motion->id . '">';
    echo '<p class="date">' . \app\components\Tools::formatMysqlDate($motion->dateCreation) . '</p>' . "\n";
    echo '<p class="title">' . "\n";

    $motionUrl = UrlHelper::createMotionUrl($motion);
    echo '<a href="' . Html::encode($motionUrl) . '" class="motionLink' . $motion->id . '">';

    $title = ($motion->title == '' ? '-' : $motion->title);
    echo ' <span class="motionTitle">' . Html::encode($title) . '</span>';

    echo '</a>';
    echo "</p>\n";
    echo '<p class="info">';
    if ($bold === 'organization') {
        echo '<span class="status">' . Html::encode($motion->consultation->title) . '</span>, ';
    }
    echo Html::encode($motion->getInitiatorsStr());
    echo '</p>';
    echo '</li>';
}
echo '</ul>';
