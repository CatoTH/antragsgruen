<?php

use app\models\db\Amendment;
use app\models\db\AmendmentComment;
use app\models\db\User;

/**
 * @var \yii\web\View $this
 * @var Amendment $amendment
 * @var \app\models\forms\CommentForm $commentForm
 */

$motion       = $amendment->getMyMotion();
$consultation = $motion->getMyConsultation();

echo '<section class="comments" data-antragsgruen-widget="frontend/Comments">';
echo '<h2 class="green">' . \Yii::t('amend', 'comments_title') . '</h2>';

$form = $commentForm;

if ($form === null || $form->paragraphNo != -1 || $form->sectionId != -1) {
    $form = new \app\models\forms\CommentForm($amendment->getMyMotionType(), null);
    $form->setDefaultData(-1, -1, User::getCurrentUser());
}

$screeningQueue = 0;
foreach ($amendment->comments as $comment) {
    if ($comment->status === AmendmentComment::STATUS_SCREENING) {
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

$screenAdmin = User::havePrivilege($consultation, User::PRIVILEGE_SCREENING);
foreach ($amendment->getVisibleComments($screenAdmin, -1, null) as $comment) {
    /** @var AmendmentComment $comment */
    echo $this->render('@app/views/motion/_comment', ['comment' => $comment]);
}

echo $form->renderFormOrErrorMessage();

echo '</section>';
