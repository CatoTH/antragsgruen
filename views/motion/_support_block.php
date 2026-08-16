<?php

use app\components\RequestContext;
use app\models\db\ISupporter;
use app\models\db\User;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\SupportBase;
use yii\helpers\Html;

/**
 * @var User|null $user
 * @var SupportBase $supportType
 * @var \app\controllers\UserController $controller
 */
$controller = $this->context;
$layout     = $controller->layoutParams;

$nameReadOnly  = ($user && ($user->fixedData & User::FIXED_NAME) ? 'readonly' : '');
$orgaReadOnly  = ($user && ($user->fixedData & User::FIXED_ORGA) ? 'readonly' : '');
$name          = ($user ? $user->name : '');
$disableSubmit = '';
$settings      = $supportType->getSettingsObj();

$canSupportAsPerson = $settings->supporterCanBePerson;
$canSupportAsOrga   = $settings->supporterCanBeOrganization;
if (!$canSupportAsPerson && !$canSupportAsOrga) {
    // Misconfiguration; supporting as a natural person is the historical default
    $canSupportAsPerson = true;
}
$hasPersonTypeChoice = ($canSupportAsPerson && $canSupportAsOrga);
$defaultPersonType   = ($canSupportAsPerson ? ISupporter::PERSON_NATURAL : ISupporter::PERSON_ORGANIZATION);

// As a natural person, the organization is the (optional or mandatory) affiliation;
// as an organization, it is the supporter itself.
$showOrga = ($canSupportAsOrga || $settings->hasOrganizations);
$orgaRequiredInEveryMode = ($settings->hasOrganizations || !$canSupportAsPerson);

echo Html::beginForm('', 'post', [
    'class'                    => 'motionSupportForm',
    'data-settings'            => json_encode($settings)
]);

$layout->addJsTranslation("motion");
?>
    <script type="module" crossorigin="anonymous">
        import { motionSupportBlock } from '/js/modules/frontend/MotionSupportBlock.js';
        motionSupportBlock(document.querySelector('.motionSupportForm'));
    </script>
    <label class="supportQuestion"><?= Yii::t('motion', 'support_question') ?></label>
<?php
if ($orgaRequiredInEveryMode && $user && $user->organization === '' && $user->fixedData) {
    echo '<div class="alert alert-danger">';
    echo Yii::t('motion', 'supporting_no_orga_error');
    echo '</div>';
    $disableSubmit = 'disabled';
}

if ($hasPersonTypeChoice) {
    ?>
    <fieldset class="supportPersonTypeSelection">
        <legend class="sr-only"><?= Html::encode(Yii::t('motion', 'support_as_question')) ?></legend>
        <label class="supportAsPerson">
            <?= Html::radio('motionSupportPersonType', true, ['value' => ISupporter::PERSON_NATURAL]) ?>
            <?= Yii::t('motion', 'support_as_person') ?>
        </label>
        <label class="supportAsOrga">
            <?= Html::radio('motionSupportPersonType', false, ['value' => ISupporter::PERSON_ORGANIZATION]) ?>
            <?= Yii::t('motion', 'support_as_orga') ?>
        </label>
    </fieldset>
    <?php
} else {
    echo Html::hiddenInput('motionSupportPersonType', (string)$defaultPersonType);
}
?>
    <div class="supportBlock">
        <div class="colName">
            <?php
            $namePlaceholder = ($defaultPersonType === ISupporter::PERSON_ORGANIZATION ?
                Yii::t('motion', 'support_contact_name') : Yii::t('motion', 'support_name'));
            ?>
            <input type="text" name="motionSupportName" class="form-control" required <?= $nameReadOnly ?>
                   value="<?= Html::encode($name) ?>"
                   data-label-person="<?= Html::encode(Yii::t('motion', 'support_name')) ?>"
                   data-label-orga="<?= Html::encode(Yii::t('motion', 'support_contact_name')) ?>"
                   title="<?= Html::encode($namePlaceholder) ?>"
                   placeholder="<?= Html::encode($namePlaceholder) ?>">
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
        if ($showOrga) {
            $orga = ($user ? $user->organization : '');
            $orgaRequired = ($orgaRequiredInEveryMode || $defaultPersonType === ISupporter::PERSON_ORGANIZATION);
            echo '<div class="colOrga">';
            echo '<input type="text" name="motionSupportOrga" class="form-control"
                           value="' . Html::encode($orga) . '"
                           placeholder="' . Html::encode(Yii::t('motion', 'support_orga')) . '"
                           title="' . Html::encode(Yii::t('motion', 'support_orga')) . '"
                           ' . ($orgaRequired ? 'required' : '') . ' ' . $orgaReadOnly . '>';
            echo '</div>';
        }
        if ($settings->contactGender !== InitiatorForm::CONTACT_NONE) {
            $genderChoices = array_merge(
                ['' => Yii::t('initiator', 'gender') . ':'],
                SupportBase::getGenderSelection()
            );

            $genderPreselected = RequestContext::getSession()->get('user_gender');
            echo '<div class="colGender' . ($defaultPersonType === ISupporter::PERSON_ORGANIZATION ? ' hidden' : '') . '">';
            echo Html::dropDownList(
                'motionSupportGender',
                $genderPreselected,
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
