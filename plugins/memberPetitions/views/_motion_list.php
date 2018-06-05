<?php

use app\components\UrlHelper;
use app\models\db\IMotion;
use app\models\db\Motion;
use app\plugins\memberPetitions\Tools;
use yii\helpers\Html;

/**
 * @var Motion[] $motions
 * @var string $bold
 * @var bool $statusClustering
 */

if (count($motions) === 0) {
    echo \Yii::t('memberPetitions', 'none');
    return;
}

if ($statusClustering) {
    $lastPhase = 0;

    usort($motions, function (IMotion $motion1, IMotion $motion2) {
        $phase1 = Tools::getMotionPhaseNumber($motion1);
        $phase2 = Tools::getMotionPhaseNumber($motion2);
        if ($phase1 < $phase2) {
            return -1;
        } elseif ($phase1 > $phase2) {
            return 1;
        } else {
            $created1 = Tools::getMotionTimestamp($motion1);
            $created2 = Tools::getMotionTimestamp($motion2);
            if ($created1 < $created2) {
                return -1;
            } elseif ($created1 > $created2) {
                return 1;
            } else {
                return 0;
            }
        }
    });
}

echo '<ul class="motionList motionListPetitions">';
foreach ($motions as $motion) {
    $status = $motion->getFormattedStatus();

    $motionPhase     = Tools::getMotionPhaseNumber($motion);
    $motionPhaseName = null;
    switch ($motionPhase) {
        case 1:
            $motionPhaseName = \Yii::t('memberPetitions', 'status_discussing');
            break;
        case 2:
            $motionPhaseName = \Yii::t('memberPetitions', 'status_collecting');
            break;
        case 3:
            $motionPhaseName = \Yii::t('memberPetitions', 'status_unanswered');
            break;
        case 4:
            $motionPhaseName = \Yii::t('memberPetitions', 'status_answered');
            break;
    }

    if ($statusClustering) {
        if ($motionPhase !== $lastPhase) {
            echo '<li class="sortitem green" data-phase="1" data-created="' . $motionPhase . '">' .
                $motionPhaseName . '</li>';
            $lastPhase = $motionPhase;
        }
    }

    $cssClasses   = ['sortitem', 'motion'];
    $cssClasses[] = 'motionRow' . $motion->id;
    $cssClasses[] = 'phase' . $motionPhase;
    foreach ($motion->tags as $tag) {
        $cssClasses[] = 'tag' . $tag->id;
    }

    $commentCount   = $motion->getNumOfAllVisibleComments(false);
    $amendmentCount = count($motion->getVisibleAmendments(false));
    $publication    = $motion->datePublication;

    echo '<li class="' . implode(' ', $cssClasses) . '" ' .
        'data-phase="' . $motionPhase . '"' .
        'data-created="' . Tools::getMotionTimestamp($motion) . '" ' .
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

    if ($bold !== 'organization') {
        echo '<span class="phaseName">. ' . $motionPhaseName;
        if ($motion->status === IMotion::STATUS_COLLECTING_SUPPORTERS) {
            $max = $motion->getMyMotionType()->getMotionSupportTypeClass()->getMinNumberOfSupporters();
            $curr = count($motion->getSupporters());
            echo ' (' . $curr . ' / ' . $max . ')';
        }
        echo '</span>';
    }

    $deadline = Tools::getPetitionResponseDeadline($motion);
    if ($deadline) {
        echo ', ' . \Yii::t('memberPetitions', 'index_remaining') . ': ';
        echo \app\components\Tools::formatRemainingTime($deadline);
    }

    $deadline = Tools::getDiscussionUntil($motion);
    if ($deadline) {
        echo ', ' . \Yii::t('memberPetitions', 'index_remaining') . ': ';
        echo \app\components\Tools::formatRemainingTime($deadline);
    }
    echo '</p>';
    echo '</li>';
}
echo '</ul>';
