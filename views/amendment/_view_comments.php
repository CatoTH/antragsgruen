<?php

use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\User;
use app\views\motion\LayoutHelper as MotionLayoutHelper;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\forms\CommentForm $commentForm
 */

$motion       = $amendment->getMyMotion();
$consultation = $motion->getMyConsultation();

echo '<section class="comments"><h2 class="green">' . \Yii::t('amend', 'comments_title') . '</h2>';

$form        = $commentForm;
$screenAdmin = User::currentUserHasPrivilege($consultation, User::PRIVILEGE_SCREENING);

if ($form === null || $form->paragraphNo != -1 || $form->sectionId != -1) {
    $form              = new \app\models\forms\CommentForm();
    $form->paragraphNo = -1;
    $form->sectionId   = -1;
    $user              = User::getCurrentUser();
    if ($user) {
        $form->name  = $user->name;
        $form->email = $user->email;
    }
}

$baseLink     = UrlHelper::createAmendmentUrl($amendment);
$visibleStati = [AmendmentComment::STATUS_VISIBLE];
if ($screenAdmin) {
    $visibleStati[] = AmendmentComment::STATUS_SCREENING;
}
$screeningQueue = 0;
foreach ($amendment->comments as $comment) {
    if ($comment->status == AmendmentComment::STATUS_SCREENING) {
        $screeningQueue++;
    }
}
if ($screeningQueue > 0) {
    echo '<div class="commentScreeningQueue">';
    if ($screeningQueue == 1) {
        echo \Yii::t('amend', 'comments_screening_queue_1');
    } else {
        echo str_replace('%NUM%', $screeningQueue, \Yii::t('amend', 'comments_screening_queue_x'));
    }
    echo '</div>';
}
foreach ($amendment->comments as $comment) {
    if ($comment->paragraph == -1 && in_array($comment->status, $visibleStati)) {
        $commLink = UrlHelper::createAmendmentCommentUrl($comment);
        MotionLayoutHelper::showComment($comment, $screenAdmin, $baseLink, $commLink);
    }
}

if ($motion->motionType->getCommentPolicy()->checkCurrUser()) {
    MotionLayoutHelper::showCommentForm($form, $consultation, -1, -1);
} elseif ($motion->motionType->getCommentPolicy()->checkCurrUser(true, true)) {
    echo '<div class="alert alert-info" style="margin: 19px;" role="alert">
        <span class="glyphicon glyphicon-log-in"></span>&nbsp; ' .
        \Yii::t('amend', 'comments_please_log_in') . '</div>';
}
echo '</section>';
