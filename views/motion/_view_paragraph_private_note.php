<?php

use app\components\HTMLTools;
use yii\helpers\Html;

/**
 * @var \app\models\db\Motion $motion
 * @var int $sectionId
 * @var int $paragraphNo
 */

$comment = $motion->getPrivateComment($sectionId, $paragraphNo);

$sectionTitle = null;
foreach ($motion->getActiveSections() as $section) {
    if ($section->sectionId === $sectionId) {
        $sectionTitle = $section->getSettings()->title;
    }
}
$noteSrc = ['%NO%', '%TITLE%'];
$noteRepl = [($paragraphNo + 1), $sectionTitle];
?>
<section class="privateParagraphNoteHolder">
    <?php
    if (!$comment) {
        ?>
        <div class="privateParagraphNoteOpener hidden">
            <button class="btn btn-link btn-xs" tabindex="0" type="button">
                <span class="glyphicon glyphicon-pushpin" aria-hidden="true"></span>
                <?= str_replace($noteSrc, $noteRepl, Yii::t('motion', 'private_notes_para_open')) ?>
            </button>
        </div>
        <?php
    }
    if ($comment) {
        ?>
        <blockquote class="privateParagraph<?= $comment ? '' : ' hidden' ?>" id="comm<?= $comment->id ?>">
            <button class="btn btn-link btn-xs btnEdit">
                <span class="glyphicon glyphicon-edit" aria-hidden="true"></span>
                <span class="sr-only"><?= str_replace($noteSrc, $noteRepl, Yii::t('motion', 'private_notes_para_edit')) ?></span>
            </button>
            <?= HTMLTools::textToHtmlWithLink($comment ? $comment->text : '') ?>
        </blockquote>
        <?php
    }
    ?>
    <?= Html::beginForm('', 'post', ['class' => 'form-inline hidden']) ?>
    <label>
        <span class="sr-only"><?= str_replace($noteSrc, $noteRepl, Yii::t('motion', 'private_notes_para_write')) ?></span>
        <textarea class="form-control" name="noteText" placeholder="<?= str_replace($noteSrc, $noteRepl, Yii::t('motion', 'private_notes_para_write')) ?>"
        ><?= Html::encode($comment ? $comment->text : '') ?></textarea>
    </label>
    <input type="hidden" name="paragraphNo" value="<?= $paragraphNo ?>">
    <input type="hidden" name="sectionId" value="<?= $sectionId ?>">
    <button type="submit" name="savePrivateNote" class="btn btn-success">
        <?= str_replace($noteSrc, $noteRepl, Yii::t('motion', 'private_notes_para_save')) ?>
    </button>
    <?= Html::endForm() ?>
</section>
