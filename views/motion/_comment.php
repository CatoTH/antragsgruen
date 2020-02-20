<?php

use app\components\{Tools, HTMLTools};
use app\models\db\{IComment, MotionComment, User};
use app\models\forms\CommentForm;
use yii\helpers\Html;

/**
 * @var IComment $comment
 */

$imotion       = $comment->getIMotion();
$screening     = ($comment->status === IComment::STATUS_SCREENING);
$screenAdmin   = User::havePrivilege($imotion->getMyConsultation(), User::PRIVILEGE_SCREENING);
$commentPolicy = $imotion->getMyMotionType()->getCommentPolicy();
$canReply      = (!$comment->parentCommentId && $commentPolicy->checkCurrUserComment(false, false));

?>

<article class="motionComment hoverHolder" id="comment<?= $comment->id ?>" data-id="<?= $comment->id ?>">
    <div class="date"><?= Tools::formatMysqlDate($comment->dateCreation) ?></div>
    <h3 class="commentHeader"><?= Html::encode($comment->name) ?>:
        <?php
        if ($screening) {
            echo ' <span class="screeningHint">(' . Yii::t('comment', 'not_screened_yet') . ')</span>';
        }
        ?>
    </h3>

    <div class="commentText">
        <?= HTMLTools::textToHtmlWithLink($comment->text) ?>
    </div>

    <?php
    if ($screening) {
        echo Html::beginForm($comment->getLink(), 'post', ['class' => 'screening']);
        ?>
        <div>
            <button type="submit" class="btn btn-success" name="commentScreeningAccept">
                <span class="glyphicon glyphicon-thumbs-up"></span> <?= Yii::t('comment', 'screen_yes') ?>
            </button>
        </div>
        <div>
            <button type="submit" class="btn btn-danger" name="commentScreeningReject">
                <span class="glyphicon glyphicon-thumbs-down"></span> <?= Yii::t('comment', 'screen_no') ?>
            </button>
        </div>
        <?php
        echo Html::endForm();
    }
    ?>
    <div class="commentBottom">
        <?php
        if ($comment->status === IComment::STATUS_VISIBLE && $comment->canDelete(User::getCurrentUser())) {
            echo Html::beginForm($imotion->getLink(), 'post', ['class' => 'entry delLink']);
            echo '<input type="hidden" name="commentId" value="' . $comment->id . '">';
            echo '<input type="hidden" name="deleteComment" value="on">';
            echo '<button class="link" type="submit">';
            echo '<span class="glyphicon glyphicon-trash"></span></button>';
            echo Html::endForm();
        }

        $link     = '<span class="glyphicon glyphicon-link"></span>';
        $linkOpts = ['class' => 'entry link', 'title' => Yii::t('comment', 'link_comment')];
        echo Html::a($link, $comment->getLink(), $linkOpts);

        if ($canReply) {
            $replyToId = ($comment->parentCommentId ? $comment->parentCommentId : $comment->id);
            echo '<button type="button" class="entry btn btn-link replyButton" data-reply-to="' . $replyToId . '">';
            echo '<span class="glyphicon glyphicon-pencil"></span> ' . Yii::t('comment', 'reply_btn') . '</button>';
        }
        ?>
    </div>
</article>

<?php
if (count($comment->replies) > 0 || $canReply) {
    echo '<div class="motionCommentReplies">';

    foreach ($comment->getIMotion()->getVisibleComments($screenAdmin, $comment->paragraph, $comment->id) as $reply) {
        echo $this->render('@app/views/motion/_comment', ['comment' => $reply]);
    }

    if ($canReply) {
        $replyForm = new CommentForm($imotion->getMyMotionType(), $comment);
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
