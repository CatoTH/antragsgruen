<?php

use app\components\Tools;
use app\components\HTMLTools;
use app\models\db\IComment;
use app\models\db\MotionComment;
use app\models\db\User;
use app\models\forms\CommentForm;
use yii\helpers\Html;

/**
 * @var IComment $comment
 * @var bool $imadmin
 * @var string $baseLink
 * @var string $commLink
 * @var \app\models\db\ConsultationMotionType $motionType
 */

$screening = ($comment->status == IComment::STATUS_SCREENING);

?>

<article class="motionComment hoverHolder" id="comment<?= $comment->id ?>" data-id="<?= $comment->id ?>">
    <div class="date"><?= Tools::formatMysqlDate($comment->dateCreation) ?></div>
    <h3 class="commentHeader"><?= Html::encode($comment->name) ?>:
        <?php
        if ($screening) {
            echo ' <span class="screeningHint">(' . \Yii::t('comment', 'not_screened_yet') . ')</span>';
        }
        ?>
    </h3>

    <div class="commentText">
        <?= HTMLTools::textToHtmlWithLink($comment->text) ?>
    </div>

    <?php
    if ($screening && $imadmin) {
        echo Html::beginForm($commLink, 'post', ['class' => 'screening']);
        ?>
        <div>
            <button type="submit" class="btn btn-success" name="commentScreeningAccept">
                <span class="glyphicon glyphicon-thumbs-up"></span> <?= \Yii::t('comment', 'screen_yes') ?>
            </button>
        </div>
        <div>
            <button type="submit" class="btn btn-danger" name="commentScreeningReject">
                <span class="glyphicon glyphicon-thumbs-down"></span> <?= \Yii::t('comment', 'screen_no') ?>
            </button>
        </div>
        <?php
        echo Html::endForm();
    }
    ?>
    <div class="commentBottom">
        <?php
        if ($comment->status === IComment::STATUS_VISIBLE && $comment->canDelete(User::getCurrentUser())) {
            echo Html::beginForm($baseLink, 'post', ['class' => 'entry delLink']);
            echo '<input type="hidden" name="commentId" value="' . $comment->id . '">';
            echo '<input type="hidden" name="deleteComment" value="on">';
            echo '<button class="link" type="submit">';
            echo '<span class="glyphicon glyphicon-trash"></span></button>';
            echo Html::endForm();
        }

        $link = '<span class="glyphicon glyphicon-link"></span>';
        echo Html::a($link, $commLink, ['class' => 'entry link', 'title' => \Yii::t('comment', 'link_comment')]);
        if ($comment->parentCommentId === null) {
            echo '<button type="button" class="entry btn btn-link replyButton">';
            echo '<span class="glyphicon glyphicon-pencil"></span> ' . \Yii::t('comment', 'reply_btn') . '</button>';
        }
        ?>
    </div>
</article>

<?php
$canReply = (!$comment->parentCommentId && $motionType->getCommentPolicy()->checkCurrUserComment(false, false));
if (count($comment->replies) > 0 || $canReply) {
    echo '<div class="motionCommentReplies">';

    if ($canReply) {
        $replyForm = new CommentForm($motionType, $comment);
        if (is_a($comment, MotionComment::class)) {
            /** @var MotionComment $comment */
            $replyForm->setDefaultData($comment->paragraph, $comment->sectionId, User::getCurrentUser());
        } else {
            $replyForm->setDefaultData(-1, -1, User::getCurrentUser());
        }
        echo $replyForm->renderFormOrErrorMessage(true);
    }
    echo '</div>';
}