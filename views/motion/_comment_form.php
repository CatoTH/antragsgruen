<?php

use app\components\AntiSpam;
use app\models\db\IComment;
use app\models\db\User;
use app\models\forms\CommentForm;
use \Yii\Helpers\Html;

/**
 * @var int $paragraphNo
 * @var int $sectionId
 * @var CommentForm $form
 * @var \app\models\db\Consultation $consultation
 * @var IComment $isReplyTo
 */

$user = User::getCurrentUser();

$classes = 'commentForm motionComment form-horizontal';
if ($isReplyTo) {
    $classes   .= ' replyComment replyTo' . $isReplyTo->id . ' hidden';
    $title     = \Yii::t('comment', 'comment_reply_title');
    $formIdPre = 'comment_' . $sectionId . '_' . $paragraphNo . '_' . $isReplyTo->id;
} else {
    $title     = \Yii::t('comment', 'comment_write_title');
    $formIdPre = 'comment_' . $sectionId . '_' . $paragraphNo;
}
echo Html::beginForm('', 'post', ['class' => $classes, 'id' => $formIdPre . '_form']);

if ($user && $user->name) {
    echo '<div class="commentName">' . Html::encode($form->name) . ' (' . Html::encode($form->email) . ')</div>';
}
echo '<h3 class="commentHeader commentWriteHeader">' . $title . '</h3>';

if (\Yii::$app->user->isGuest) {
    echo AntiSpam::getJsProtectionHint($consultation->id);
}

?>
    <input type="hidden" name="comment[paragraphNo]" value="<?= $paragraphNo ?>">
    <input type="hidden" name="comment[sectionId]" value="<?= $sectionId ?>">
<?php
if ($isReplyTo) {
    echo '<input type="hidden" name="comment[parentCommentId]" value="' . $isReplyTo->id . '">';
}

if ($user && $user->name) {
    ?>
    <div class="commentFullTextarea">
        <textarea name="comment[text]" title="<?= Html::encode(\Yii::t('comment', 'text')) ?>" class="form-control"
                  rows="5" id="<?= $formIdPre ?>_text"><?= Html::encode($form->text) ?></textarea>
    </div>
    <?php
} else {
    ?>
    <div class="form-group">
        <label for="<?= $formIdPre ?>_name" class="control-label col-sm-3">
            <?= \Yii::t('comment', 'name') ?>:
        </label>
        <div class="col-sm-9">
            <input type="text" class="form-control col-sm-9" id="<?= $formIdPre ?>_name"
                   name="comment[name]" value="<?= Html::encode($form->name) ?>" required autocomplete="name">
        </div>
    </div>
    <div class="form-group">
        <label for="<?= $formIdPre ?>_email" class="control-label col-sm-3">
            <?= \Yii::t('comment', 'email') ?>:
        </label>
        <div class="col-sm-9">
            <input type="email" class="form-control" id="<?= $formIdPre ?>_email"
                   autocomplete="email" name="comment[email]"
                   value="<?= Html::encode($form->email) ?>"
                <?= ($consultation->getSettings()->commentNeedsEmail ? ' required' : '') ?>>
        </div>
    </div>
    <div class="form-group">
        <label for="<?= $formIdPre ?>_text" class="control-label col-sm-3"><?= \Yii::t('comment', 'text') ?>
            :</label>
        <div class="col-sm-9">
            <textarea name="comment[text]" title="Text" class="form-control" rows="5"
                      id="<?= $formIdPre ?>_text"><?= Html::encode($form->text) ?></textarea>
        </div>
    </div>
    <?php
}
?>

    <div class="submitrow">
        <button class="btn btn-success" name="writeComment" type="submit">
            <?= \Yii::t('comment', 'submit_comment') ?>
        </button>
    </div>
<?php
echo Html::endForm();
