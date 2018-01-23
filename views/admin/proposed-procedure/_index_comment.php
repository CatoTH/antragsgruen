<?php

use app\components\HTMLTools;
use app\components\Tools;
use app\components\UrlHelper;
use app\models\db\Amendment;
use app\models\db\IAdminComment;
use app\models\db\IMotion;
use yii\helpers\Html;

/**
 * @var IMotion $item
 */

if (is_a($item, Amendment::class)) {
    $postUrl = UrlHelper::createUrl('admin/proposed-procedure/save-amendment-comment');
} else {
    $postUrl = UrlHelper::createUrl('admin/proposed-procedure/save-motion-comment');
}

?>
<td class="comments" data-post-url="<?= Html::encode($postUrl) ?>">
    <?php
    $types           = [IAdminComment::PROPOSED_PROCEDURE];
    $currentComments = $item->getAdminComments($types, IAdminComment::SORT_ASC);
    ?>
    <div class="notWriting">
        <button class="btn btn-sm btn-link pull-left writingOpener" type="button">
            <span class="glyphicon glyphicon-edit"></span>
        </button>
        <div class="commentList <?= (count($currentComments) > 0 ? 'hasContent' : '') ?>">
            <?php
            if (count($currentComments) > 0) {
                foreach ($currentComments as $currentComment) {
                    $user = $currentComment->user;
                    ?>
                    <article class="comment">
                        <span class="name"><?= Html::encode($user ? $user->name : '-') ?></span>
                        (<span class="date"><?= Tools::formatMysqlDateTime($currentComment->dateCreation) ?></span>):
                        <span class="comment"><?= HTMLTools::textToHtmlWithLink($currentComment->text) ?></span>
                    </article>
                    <?php
                }
            }
            ?>
            <article class="comment template">
                <span class="name"></span>
                (<span class="date"></span>):
                <span class="comment"></span>
            </article>
        </div>
    </div>
    <section class="writing">
        <textarea class="form-control" name="comment" required
                  placeholder="<?= Html::encode(\Yii::t('amend', 'proposal_comment_placeh')) ?>"
        ></textarea>
        <button class="btn btn-default submitComment">
            <?= \Yii::t('amend', 'proposal_comment_write') ?>
        </button>
        <button class="pull-right btn btn-white cancelWriting">
            <span class="glyphicon glyphicon-remove"></span>
        </button>
    </section>
</td>