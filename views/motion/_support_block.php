<?php

use app\models\settings\InitiatorForm;
use app\models\supportTypes\SupportBase;
use yii\helpers\Html;

/**
 * @var \app\models\db\User|null $user
 * @var \app\models\supportTypes\SupportBase $supportType
 */

$fixedReadOnly = ($user && $user->fixedData ? 'readonly' : '');
$name          = ($user ? $user->name : '');
$disableSubmit = '';
$settings      = $supportType->getSettingsObj();

echo Html::beginForm('', 'post', [
    'class'                    => 'motionSupportForm',
    'data-antragsgruen-widget' => 'frontend/MotionSupportBlock',
    'data-settings'            => json_encode($settings)
]);

?>
    <label style="margin-top: 10px;"><?= \Yii::t('motion', 'support_question') ?></label>
<?php
if ($settings->hasOrganizations && $user && $user->organization === '' && $user->fixedData) {
    echo '<div class="alert alert-danger" role="alert">';
    echo \Yii::t('motion', 'supporting_no_orga_error');
    echo '</div>';
    $disableSubmit = 'disabled';
}

$cols = 2;
if ($settings->hasOrganizations) {
    $cols++;
}
if ($settings->contactGender !== InitiatorForm::CONTACT_NONE) {
    $cols++;
}
$width = (12 / $cols);

?>
    <div class="row supportBlock fuelux">
        <div class="col-md-<?= $width ?>">
            <input type="text" name="motionSupportName" class="form-control" required <?= $fixedReadOnly ?>
                   value="<?= Html::encode($name) ?>"
                   placeholder="<?= Html::encode(\Yii::t('motion', 'support_name')) ?>">
        </div>
        <?php
        if ($settings->hasOrganizations) {
            $orga = ($user ? $user->organization : '');
            echo '<div class="col-md-' . $width . '">';
            echo '<input type="text" name="motionSupportOrga" class="form-control"
                           value="' . Html::encode($orga) . '"
                           placeholder="' . Html::encode(\Yii::t('motion', 'support_orga')) . '" 
                           required ' . $fixedReadOnly . '>';
            echo '</div>';
        }
        if ($settings->contactGender !== InitiatorForm::CONTACT_NONE) {
            $genderChoices = array_merge(
                ['' => \Yii::t('initiator', 'gender') . ':'],
                SupportBase::getGenderSelection()
            );
            echo '<div class="col-md-' . $width . '">';
            echo \app\components\HTMLTools::fueluxSelectbox(
                'motionSupportGender',
                $genderChoices,
                '',
                ['id' => 'motionSupportGender'],
                true
            );
            echo '</div>';
        }
        ?>
        <div class="col-md-<?= $width ?>" style="text-align: right">
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
echo Html::endForm();
