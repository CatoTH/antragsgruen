<?php

use app\models\db\{AmendmentComment, MotionComment};
use yii\helpers\Html;

/**
 * @var MotionComment[] $myMotionComments
 * @var AmendmentComment[] $myAmendmentComments
 */

if (count($myMotionComments) === 0 && count($myAmendmentComments) === 0) {
    return;
}

$motionComments = [];
foreach ($myMotionComments as $comment) {
    if (!isset($motionComments[$comment->motionId])) {
        $motionComments[$comment->motionId] = [];
    }
    if ($comment->paragraph === -1) {
        $motionComments[$comment->motionId][] = $comment->text;
    } else {
        $motionComments[$comment->motionId][] = str_replace('%NO%', (string) $comment->paragraph, Yii::t('motion', 'private_notes_para')) .
                   ': ' . $comment->text;
    }
}

$amendmentComments = [];
foreach ($myAmendmentComments as $comment) {
    if (!isset($amendmentComments[$comment->amendmentId])) {
        $amendmentComments[$comment->amendmentId] = [];
    }
    if ($comment->paragraph === -1) {
        $amendmentComments[$comment->amendmentId][] = $comment->text;
    } else {
        $amendmentComments[$comment->amendmentId][] = str_replace('%NO%', (string) $comment->paragraph, Yii::t('motion', 'private_notes_para')) .
                                                ': ' . $comment->text;
    }
}

echo '<div class="hidden privateCommentList" data-antragsgruen-widget="frontend/MotionListPrivateComments">';
foreach ($motionComments as $motionId => $commentTexts) {
    $tooltip = Html::encode(implode(" - ", $commentTexts));

    echo '<a href="#" class="privateCommentsIndicator" data-target-type="motion" data-target-id="' . $motionId . '">';
    echo '<span class="glyphicon glyphicon-pushpin" data-toggle="tooltip" data-placement="right" ' .
            'aria-label="' . Html::encode(Yii::t('base', 'aria_tooltip')) . ': ' . $tooltip . '" ' .
            'data-original-title="' . $tooltip . '"></span>';
    echo '</a>';
}

foreach ($amendmentComments as $amendmentId => $commentTexts) {
    $tooltip = Html::encode(implode(" - ", $commentTexts));

    echo '<a href="#" class="privateCommentsIndicator" data-target-type="amendment" data-target-id="' . $amendmentId . '">';
    echo '<span class="glyphicon glyphicon-pushpin" data-toggle="tooltip" data-placement="right" ' .
            'aria-label="' . Html::encode(Yii::t('base', 'aria_tooltip')) . ': ' . $tooltip . '" ' .
            'data-original-title="' . $tooltip . '"></span>';
    echo '</a>';
}
echo '</div>';
