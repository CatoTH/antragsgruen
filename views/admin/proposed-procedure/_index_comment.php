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

?>
<td class="comment" data-antragsgruen-widget="backend/ProcedureComment">
    <?php
    $types          = [IAdminComment::PROCEDURE_OVERVIEW, IAdminComment::PROCEDURE_DETAILS];
    $currentComment = $item->getAdminComments($types, IAdminComment::SORT_DESC, 1);
    ?>
    <div class="notWriting">
        <button class="btn btn-sm btn-link pull-left writingOpener" type="button">
            <span class="glyphicon glyphicon-edit"></span>
        </button>
        <?php
        if (count($currentComment) > 0) {
            $currentComment = $currentComment[0];
            $user           = $currentComment->user;
            ?>
            <article class="currentComment">
                <span class="name"><?= Html::encode($user ? $user->name : '-') ?></span>
                (<span class="date"><?= Tools::formatMysqlDateTime($currentComment->dateCreation) ?></span>):
                <span class="comment"><?= HTMLTools::textToHtmlWithLink($currentComment->text) ?></span>
            </article>
            <?php
        } else {
            ?>
            <article class="currentComment empty">
                <span class="name"></span>
                (<span class="date"></span>):
                <span class="comment"></span>
            </article>
            <?php
        }
        ?>
    </div>
    <?php
    if (is_a($item, Amendment::class)) {
        $postUrl = UrlHelper::createUrl('admin/proposed-procedure/save-amendment-comment');
    } else {
        $postUrl = UrlHelper::createUrl('admin/proposed-procedure/save-motion-comment');
    }
    echo Html::beginForm($postUrl, 'post', ['data-id' => $item->id, 'class' => 'writing']);
    ?>
    <textarea class="form-control" name="comment" required
              placeholder="<?= Html::encode(\Yii::t('amend', 'proposal_comment_placeh')) ?>"
    ></textarea>
    <button class="btn btn-default" type="submit">
        <?= \Yii::t('amend', 'proposal_comment_write') ?>
    </button>
    <?= Html::endForm() ?>
</td>