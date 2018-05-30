<?php

use app\components\AntiSpam;
use app\models\db\User;
use app\models\forms\CommentForm;
use \Yii\Helpers\Html;

/**
 * @var int $paragraphNo
 * @var int $sectionId
 * @var CommentForm $form
 * @var \app\models\db\Consultation $consultation
 */

echo Html::beginForm('', 'post', ['class' => 'commentForm form-horizontal row']);
?>
    <fieldset class="col-md-8 col-md-offset-2">
        <legend><?= \Yii::t('comment', 'comment_write_title') ?></legend>
        <?php
        if (\Yii::$app->user->isGuest) {
            echo AntiSpam::getJsProtectionHint($consultation->id);
        }
        $user = User::getCurrentUser();

        $formIdPre     = 'comment_' . $sectionId . '_' . $paragraphNo;
        $fixedReadOnly = ($user && $user->fixedData ? 'readonly' : '');

        ?>
        <input type="hidden" name="comment[paragraphNo]" value="<?= $paragraphNo ?>">
        <input type="hidden" name="comment[sectionId]" value="<?= $sectionId ?>">

        <div class="form-group">
            <label for="<?= $formIdPre ?>_name" class="control-label col-sm-3">
                <?= \Yii::t('comment', 'name') ?>:
            </label>
            <div class="col-sm-9">
                <input type="text" class="form-control col-sm-9" id="<?= $formIdPre ?>_name" <?= $fixedReadOnly ?>
                       name="comment[name]" value="<?= Html::encode($form->name) ?>" required autocomplete="name">
            </div>
        </div>
        <div class="form-group">
            <label for="<?= $formIdPre ?>_email" class="control-label col-sm-3">
                <?= \Yii::t('comment', 'email') ?>:
            </label>
            <div class="col-sm-9">
                <input type="email" class="form-control" id="<?= $formIdPre ?>_email"
                       autocomplete="email" <?= $fixedReadOnly ?> name="comment[email]"
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

        <div class="submitrow">
            <button class="btn btn-success" name="writeComment"
                    type="submit"><?= \Yii::t('comment', 'submit_comment') ?></button>
        </div>
    </fieldset>
<?php
echo Html::endForm();
