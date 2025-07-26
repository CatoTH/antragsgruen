<?php

/**
 * @var \app\models\db\IProposal $proposal
 * @var bool $showVisibility
 */

if (!isset($showVisibility)) {
    $showVisibility = true;
}

$amendmentStatusVerbs = $proposal->getMyConsultation()->getStatuses()->getStatusesAsVerbs();

if ($proposal->userStatus !== null || isset($amendmentStatusVerbs[$proposal->proposalStatus])) {
    echo '<div class="statusIcons proposalStatusIcons">';
    if ($proposal->userStatus !== null) {
        if ($proposal->userStatus === \app\models\db\IMotion::STATUS_ACCEPTED) {
            $title = Yii::t('admin', 'list_prop_user_accepted');
            echo '<span class="glyphicon glyphicon-ok accepted" title="' . $title . '"></span>';
        } else {
            echo '???'; // Not yet supported
        }
    } elseif ($proposal->proposalFeedbackHasBeenRequested()) {
        $title = Yii::t('admin', 'list_prop_user_asked');
        echo '<span class="asked" title="' . $title . '">❓</span>';
    }
    /*
       else {
        $title = Yii::t('admin', 'list_prop_user_not_asked');
        echo '<span class="not_asked" title="' . $title . '">❔</span>';
    }
    */

    if ($showVisibility) {
        if ($proposal->isProposalPublic()) {
            $title = Yii::t('admin', 'list_prop_visible');
            echo '<span class="glyphicon glyphicon-eye-open visible" title="' . $title . '"></span>';
        } else {
            $title = Yii::t('admin', 'list_prop_invisible');
            echo '<span class="glyphicon glyphicon-eye-close notVisible" title="' . $title . '"></span>';
        }
    }
    echo '</div>';
}
