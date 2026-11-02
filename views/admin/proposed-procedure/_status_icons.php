<?php

use app\components\Html;

/**
 * @var \app\models\db\IProposal $proposal
 * @var \app\models\forms\AdminMotionFilterForm|null $search
 * @var bool $showVisibility
 */

if (!isset($showVisibility)) {
    $showVisibility = true;
}

$amendmentStatusVerbs = $proposal->getMyConsultation()->getStatuses()->getStatusesAsVerbs();

if ($proposal->userStatus !== null || isset($amendmentStatusVerbs[$proposal->proposalStatus ?? ''])) {
    echo '<div class="statusIcons proposalStatusIcons">';
    if ($proposal->userStatus !== null) {
        if ($proposal->userStatus === \app\models\db\IMotion::STATUS_ACCEPTED) {
            $title = Yii::t('admin', 'list_prop_user_accepted');
            echo '<span class="glyphicon glyphicon-ok accepted" title="' . Html::encodeTranslationForAttribute($title) . '"></span>';
        } elseif ($proposal->userStatus === \app\models\db\IMotion::STATUS_REJECTED) {
            $title = Yii::t('admin', 'list_prop_user_rejected');
            echo '<span class="glyphicon glyphicon-remove rejected" title="' . Html::encodeTranslationForAttribute($title) . '"></span>';
        } else {
            echo '???'; // Not yet supported
        }
    } elseif ($proposal->proposalFeedbackHasBeenRequested()) {
        $title = Yii::t('admin', 'list_prop_user_asked');
        echo '<span class="asked" title="' . Html::encodeTranslationForAttribute($title) . '">❓</span>';
        if ($search && $search->proposalStatus) {
            echo '(' . \app\components\Tools::formatMysqlDateTime($proposal->notifiedAt) . ')';
        }
    }
    /*
       else {
        $title = Yii::t('admin', 'list_prop_user_not_asked');
        echo '<span class="not_asked" title="' . Html::encodeTranslationForAttribute($title) . '">❔</span>';
    }
    */

    if ($showVisibility) {
        if ($proposal->isProposalPublic()) {
            $title = Yii::t('admin', 'list_prop_visible');
            echo '<span class="glyphicon glyphicon-eye-open visible" title="' . Html::encodeTranslationForAttribute($title) . '"></span>';
        } else {
            $title = Yii::t('admin', 'list_prop_invisible');
            echo '<span class="glyphicon glyphicon-eye-close notVisible" title="' . Html::encodeTranslationForAttribute($title) . '"></span>';
        }
    }
    echo '</div>';
}
