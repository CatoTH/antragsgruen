<?php

use app\models\db\User;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\SupportBase;
use yii\helpers\Html;

/**
 * @var User|null $user
 * @var SupportBase $supportType
 */

$fixedReadOnly = ($user && ($user->fixedData & User::FIXED_NAME) ? 'readonly' : '');
$name          = ($user ? $user->name : '');
$disableSubmit = '';
$settings      = $supportType->getSettingsObj();

echo Html::beginForm('', 'post', [
    'class'                    => 'motionSupportForm',
    'data-antragsgruen-widget' => 'frontend/MotionSupportBlock',
    'data-settings'            => json_encode($settings)
]);

?>
    <label class="supportQuestion"><?= Yii::t('motion', 'support_question') ?></label>
<?php
if ($settings->hasOrganizations && $user && $user->organization === '' && $user->fixedData) {
    echo '<div class="alert alert-danger">';
    echo Yii::t('motion', 'supporting_no_orga_error');
    echo '</div>';
    $disableSubmit = 'disabled';
}

?>
    <div class="supportBlock">
        <div class="colName">
            <input type="text" name="motionSupportName" class="form-control" required <?= $fixedReadOnly ?>
                   value="<?= Html::encode($name) ?>"
                   title="<?= Html::encode(Yii::t('motion', 'support_name')) ?>"
                   placeholder="<?= Html::encode(Yii::t('motion', 'support_name')) ?>">
            <?php
            if ($settings->offerNonPublicSupports) {
                ?>
                <div class="nonPublicBlock">
                    <label>
                        <?= Html::checkbox('motionSupportPublic', true) ?>
                        <?= Yii::t('motion', 'support_publicly') ?>
                        <?= \app\components\HTMLTools::getTooltipIcon(Yii::t('motion', 'support_publicly_hint')) ?>
                    </label>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        if ($settings->hasOrganizations) {
            $orga = ($user ? $user->organization : '');
            echo '<div class="colOrga">';
            echo '<input type="text" name="motionSupportOrga" class="form-control"
                           value="' . Html::encode($orga) . '"
                           placeholder="' . Html::encode(Yii::t('motion', 'support_orga')) . '"
                           required ' . $fixedReadOnly . '>';
            echo '</div>';
        }
        if ($settings->contactGender !== InitiatorForm::CONTACT_NONE) {
            $genderChoices = array_merge(
                ['' => Yii::t('initiator', 'gender') . ':'],
                SupportBase::getGenderSelection()
            );
            echo '<div class="colGender">';
            echo Html::dropDownList(
                'motionSupportGender',
                '',
                $genderChoices,
                ['id' => 'motionSupportGender', 'class' => 'stdDropdown']
            );
            echo '</div>';
        }
        ?>
        <div class="colSubmit">
            <button type="submit" name="motionSupport" class="btn btn-success" <?= $disableSubmit ?>>
                <span class="glyphicon glyphicon-thumbs-up" aria-hidden="true"></span>
                <?= Yii::t('motion', 'support') ?>
            </button>
        </div>

    </div>

<?php
if (!$user) {
    echo '<div class="loggedOutWarning">' . Yii::t('motion', 'supporting_logged_out_warning') . '</div>';
}
echo Html::endForm();
