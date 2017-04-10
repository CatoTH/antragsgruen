<?php

use yii\helpers\Html;

/**
 * @var \app\models\db\User|null $user
 * @var \app\models\supportTypes\ISupportType $supportType
 */

$fixedReadOnly = ($user && $user->fixedData ? 'readonly' : '');
$name          = ($user ? $user->name : '');
$disableSubmit = '';

?>
    <label style="margin-top: 10px;"><?= \Yii::t('motion', 'support_question') ?></label>
<?php
if ($supportType->hasOrganizations() && $user && $user->organization == '' && $user->fixedData) {
    echo '<div class="alert alert-danger" role="alert">';
    echo \Yii::t('motion', 'supporting_no_orga_error');
    echo '</div>';
    $disableSubmit = 'disabled';
}
?>
    <div class="row">
        <div class="col-md-4">
            <input type="text" name="motionSupportName" class="form-control" required <?= $fixedReadOnly ?>
                   value="<?= Html::encode($name) ?>"
                   placeholder="<?= Html::encode(\Yii::t('motion', 'support_name')) ?>">
        </div>
        <?php
        if ($supportType->hasOrganizations()) {
            $orga = ($user ? $user->organization : '');
            echo '<div class="col-md-4">';
            echo '<input type="text" name="motionSupportOrga" class="form-control"
                           value="' . Html::encode($orga) . '"
                           placeholder="' . Html::encode(\Yii::t('motion', 'support_orga')) . '" 
                           required ' . $fixedReadOnly . '>';
            echo '</div>';
        }
        ?>
        <div class="col-md-4" style="text-align: right">
            <button type="submit" name="motionSupport" class="btn btn-success" <?= $disableSubmit ?>>
                <span class="glyphicon glyphicon-thumbs-up"></span>
                <?= \Yii::t('motion', 'support') ?>
            </button>
        </div>

    </div>

<?php
if (!$user) {
    echo '<div class="row"><div class="col-md-8" style="font-size: 0.8em; margin-top: 6px;">' .
        \Yii::t('motion', 'supporting_logged_out_warning') . '</div></div>';
}
