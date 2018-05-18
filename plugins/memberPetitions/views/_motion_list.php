<?php

use app\components\UrlHelper;
use app\models\db\Motion;
use app\plugins\memberPetitions\Tools;
use app\components\Tools as DateTools;
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

    $cssClasses   = ['motion'];
    $cssClasses[] = 'motionRow' . $motion->id;
    foreach ($motion->tags as $tag) {
        $cssClasses[] = 'tag' . $tag->id;
    }

    $commentCount   = count($motion->getVisibleComments(false));
    $amendmentCount = count($motion->getVisibleAmendments(false));
    $publication    = $motion->datePublication;

    echo '<li class="' . implode(' ', $cssClasses) . '" ' .
        'data-created="' . ($publication ? DateTools::dateSql2timestamp($publication) : '0') . '" ' .
        'data-num-comments="' . $commentCount . '" ' .
        'data-num-amendments="' . $amendmentCount . '">';
    echo '<p class="stats">';

    if ($amendmentCount > 0) {
        echo '<span class="amendments"><span class="glyphicon glyphicon-flash"></span> ' . $amendmentCount . '</span>';
    }
    if ($commentCount > 0) {
        echo '<span class="comments"><span class="glyphicon glyphicon-comment"></span> ' . $commentCount . '</span>';
    }
    echo '</p>' . "\n";
    echo '<p class="title">' . "\n";

    $motionUrl = UrlHelper::createMotionUrl($motion);
    echo '<a href="' . Html::encode($motionUrl) . '" class="motionLink' . $motion->id . '">';

    $title = ($motion->title == '' ? '-' : $motion->title);
    echo ' <span class="motionTitle">' . Html::encode($title) . '</span>';

    echo '</a>';
    echo "</p>\n";
    echo '<p class="info">';
    if ($bold === 'organization') {
        echo '<span class="status">' . Html::encode($motion->getMyConsultation()->title) . '</span>, ';
    }
    echo Html::encode($motion->getInitiatorsStr()) . ', ';
    echo \app\components\Tools::formatMysqlDate($motion->dateCreation);

    $deadline = Tools::getPetitionResponseDeadline($motion);
    if ($deadline) {
        echo ', ' . \Yii::t('memberpetitions', 'index_remaining') . ': ';
        echo \app\components\Tools::formatRemainingTime($deadline);
    }

    $deadline = Tools::getDiscussionUntil($motion);
    if ($deadline) {
        echo ', ' . \Yii::t('memberpetitions', 'index_remaining') . ': ';
        echo \app\components\Tools::formatRemainingTime($deadline);
    }
    echo '</p>';
    echo '</li>';
}
echo '</ul>';
