<?php

use app\components\Tools;
use app\components\HTMLTools;
use app\models\db\IComment;
use app\models\db\User;
use yii\helpers\Html;

/**
 * @var IComment $comment
 * @var bool $imadmin
 * @var string $baseLink
 * @var string $commLink
 */

$screening = ($comment->status == IComment::STATUS_SCREENING);

?>

<article class="motionComment hoverHolder" id="comment<?= $comment->id ?>">
    <div class="date"><?= Tools::formatMysqlDate($comment->dateCreation) ?></div>
    <h3 class="commentHeader"><?= Html::encode($comment->name) ?>:

        <?php
        if ($screening) {
            echo ' <span class="screeningHint">(' . \Yii::t('comment', 'not_screened_yet') . ')</span>';
        }
        if ($comment->status == IComment::STATUS_VISIBLE && $comment->canDelete(User::getCurrentUser())) {
            echo Html::beginForm($baseLink, 'post', ['class' => 'delLink hoverElement']);
            echo '<input type="hidden" name="commentId" value="' . $comment->id . '">';
            echo '<input type="hidden" name="deleteComment" value="on">';
            echo '<button class="link" type="submit">';
            echo '<span class="glyphicon glyphicon-trash"></span></button>';
            echo Html::endForm();
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
        <div class="commentLink">
            <?= Html::a(\Yii::t('comment', 'link_comment'), $commLink, ['class' => 'hoverElement']) ?>
        </div>
    </div>
</article>
