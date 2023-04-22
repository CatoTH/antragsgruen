<?php

use app\components\{HTMLTools, Tools, UrlHelper};
use app\models\db\{Amendment, IAdminComment, IMotion};
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
    $types           = [IAdminComment::TYPE_PROPOSED_PROCEDURE];
    $currentComments = $item->getAdminComments($types, IAdminComment::SORT_ASC);
    ?>
    <div class="notWriting">
        <button class="btn btn-sm btn-link pull-left writingOpener" type="button" title="<?= Yii::t('amend', 'proposal_comment_open') ?>">
            <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
            <span class="sr-only"><?= Yii::t('amend', 'proposal_comment_open') ?></span>
        </button>
        <div class="commentList <?= (count($currentComments) > 0 ? 'hasContent' : '') ?>">
            <?php
            if (count($currentComments) > 0) {
                foreach ($currentComments as $currentComment) {
                    $user = $currentComment->getMyUser();
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
                  title="<?= Html::encode(Yii::t('amend', 'proposal_comment_placeh')) ?>"
                  placeholder="<?= Html::encode(Yii::t('amend', 'proposal_comment_placeh')) ?>"
        ></textarea>
        <button class="btn btn-default submitComment" type="button">
            <?= Yii::t('amend', 'proposal_comment_write') ?>
        </button>
        <button class="pull-right btn btn-white cancelWriting" type="button" title="<?= Yii::t('amend', 'proposal_comment_cancel') ?>">
            <span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
            <span class="sr-only"><?= Yii::t('amend', 'proposal_comment_cancel') ?></span>
        </button>
    </section>
</td>
