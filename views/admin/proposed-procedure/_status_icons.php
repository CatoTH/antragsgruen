<?php

/**
 * @var \app\models\db\IMotion $entry
 * @var bool $show_visibility
 */

if (!isset($show_visibility)) {
    $show_visibility = true;
}

$amendmentStatiVerbs = \app\models\db\IMotion::getStatiAsVerbs();

if ($entry->proposalUserStatus !== null || isset($amendmentStatiVerbs[$entry->proposalStatus])) {
    echo '<div class="statusIcons proposalStatusIcons">';
    if ($entry->proposalUserStatus !== null) {
        if ($entry->proposalUserStatus === \app\models\db\IMotion::STATUS_ACCEPTED) {
            $title = \Yii::t('admin', 'list_prop_user_accepted');
            echo '<span class="glyphicon glyphicon-ok accepted" title="' . $title . '"></span>';
        } else {
            echo '???'; // Not yet supported
        }
    } elseif ($entry->proposalFeedbackHasBeenRequested()) {
        $title = \Yii::t('admin', 'list_prop_user_asked');
        echo '<span class="asked" title="' . $title . '">❓</span>';
    } else {
        $title = \Yii::t('admin', 'list_prop_user_asked');
        echo '<span class="not_asked" title="' . $title . '">❔</span>';
    }

    if ($show_visibility) {
        if ($entry->isProposalPublic()) {
            $title = \Yii::t('admin', 'list_prop_visible');
            echo '<span class="glyphicon glyphicon-eye-open visible" title="' . $title . '"></span>';
        } else {
            $title = \Yii::t('admin', 'list_prop_invisible');
            echo '<span class="glyphicon glyphicon-eye-close notVisible" title="' . $title . '"></span>';
        }
    }
    echo '</div>';
}
