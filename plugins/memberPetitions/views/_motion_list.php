<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use yii\helpers\Html;

/**
 * @var Motion[] $motions
 */

if (count($motions) === 0) {
    echo \Yii::t('memberpetitions', 'none');
    return;
}

echo '<ul class="motionList motionListPetitions">';
foreach ($motions as $motion) {
    $status = '';
    switch ($motion->status) {
        case Motion::STATUS_COLLECTING_SUPPORTERS:
            $status = \Yii::t('memberpetitions', 'status_collecting');
            break;
        case Motion::STATUS_SUBMITTED_SCREENED:
            $status = \Yii::t('memberpetitions', 'status_unanswered');
            break;
        case Motion::STATUS_PROCESSED:
            $status = '✔ ' . \Yii::t('memberpetitions', 'status_answered');
            break;
    }
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
    echo '<span class="status">' . Html::encode($status) . '</span>, ';
    echo Html::encode($motion->getInitiatorsStr());
    echo '</p>';
    echo '</li>';
}
echo '</ul>';
