<?php

use app\components\HTMLTools;
use app\models\db\ConsultationMotionType;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\CollectBeforePublish;
use app\models\supportTypes\SupportBase;
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 */

$motionSettings    = $motionType->getMotionSupportTypeClass()->getSettingsObj();
$amendmentSettings = $motionType->getAmendmentSupportTypeClass()->getSettingsObj();

$sameInitiatorSettingsForAmendments = (json_encode($motionSettings) === json_encode($amendmentSettings));
?>
<section class="motionSupporters">
    <h3><?= Yii::t('admin', 'motion_type_initiator') ?></h3>

    <div class="form-group">
        <label class="col-md-4 control-label" for="typeSupportType">
            <?= Yii::t('admin', 'motion_type_supp_form') ?>
        </label>
        <div class="col-md-8">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="type">
            <?php
            $options = [];
            foreach (SupportBase::getImplementations() as $formId => $formClass) {
                $supporters = ($formClass::hasInitiatorGivenSupporters() || $formClass === CollectBeforePublish::class);
                $options[]  = [
                    'title'      => $formClass::getTitle(),
                    'attributes' => ['data-has-supporters' => ($supporters ? '1' : '0')],
                ];
            }
            echo HTMLTools::fueluxSelectbox(
                'motionInitiatorSettings[type]',
                $options,
                $motionSettings->type,
                ['id' => 'typeSupportType'],
                true
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_person_type') ?>
        </div>
        <div class="col-md-8 contactDetails personTypes">
            <div class="form-control">
                <label>
                    <?php
                    echo Html::checkbox('initiatorCanBePerson', $motionSettings->initiatorCanBePerson);
                    echo Yii::t('admin', 'motion_type_person_natural');
                    ?>
                </label>
                <label>
                    <?php
                    echo Html::checkbox('initiatorCanBeOrganization', $motionSettings->initiatorCanBeOrganization);
                    echo Yii::t('admin', 'motion_type_person_orga');
                    ?>
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_contact_name') ?>
        </div>
        <div class="col-md-8 contactDetails contactName">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="contactName">
            <?php
            echo Html::radioList(
                'motionInitiatorSettings[contactName]',
                $motionSettings->contactName,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_email') ?>
        </div>
        <div class="col-md-8 contactDetails contactEMail">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="contactEmail">
            <?php
            echo Html::radioList(
                'motionInitiatorSettings[contactEmail]',
                $motionSettings->contactEmail,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_phone') ?>
        </div>
        <div class="col-md-8 contactDetails contactPhone">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="contactPhone">
            <?php
            echo Html::radioList(
                'motionInitiatorSettings[contactPhone]',
                $motionSettings->contactPhone,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group formGroupResolutionDate">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_orga_resolution') ?>
        </div>
        <div class="col-md-8 contactDetails contactResolutionDate">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="hasResolutionDate">
            <?php
            echo Html::radioList(
                'motionInitiatorSettings[hasResolutionDate]',
                $motionSettings->hasResolutionDate,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group formGroupGender">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_gender') ?>
        </div>
        <div class="col-md-8 contactDetails contactGender">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="contactGender">
            <?php
            echo Html::radioList(
                'motionInitiatorSettings[contactGender]',
                $motionSettings->contactGender,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group" id="typeMinSupportersRow">
        <label class="col-md-4 control-label" for="typeMinSupporters">
            <?= Yii::t('admin', 'motion_type_supp_min') ?>
        </label>
        <div class="col-md-2">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="minSupporters">
            <input type="number" name="motionInitiatorSettings[minSupporters]" class="form-control" id="typeMinSupporters"
                   value="<?= Html::encode($motionSettings->minSupporters) ?>">
        </div>
    </div>

    <div class="form-group formGroupMinFemale" id="typeMinSupportersFemaleRow">
        <label class="col-md-4 control-label" for="typeMinSupportersFemale">
            <?= Yii::t('admin', 'motion_type_supp_female_min') ?>
        </label>
        <div class="col-md-2">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="minSupportersFemale">
            <input type="number" name="motionInitiatorSettings[minSupportersFemale]" class="form-control" id="typeMinSupportersFemale"
                   value="<?= Html::encode($motionSettings->minSupportersFemale) ?>">
        </div>
    </div>

    <div class="form-group" id="typeAllowMoreSupporters">
        <div class="checkbox col-md-8 col-md-offset-4">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="allowMoreSupporters">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'motionInitiatorSettings[allowMoreSupporters]',
                Yii::t('admin', 'motion_type_allow_more_supp'),
                $motionSettings->allowMoreSupporters
            );
            ?>
        </div>
    </div>

    <div class="form-group" id="typeHasOrgaRow">
        <div class="checkbox col-md-8 col-md-offset-4">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="hasOrganizations">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'motionInitiatorSettings[hasOrganizations]',
                Yii::t('admin', 'motion_type_ask_orga'),
                $motionSettings->hasOrganizations
            );
            ?>
        </div>
    </div>


    <div class="form-group" id="sameInitiatorSettingsForAmendments">
        <div class="checkbox col-md-8 col-md-offset-4">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'sameInitiatorSettingsForAmendments',
                Yii::t('admin', 'motion_type_same_amendment'),
                $sameInitiatorSettingsForAmendments
            );
            ?>
        </div>
    </div>
</section>


<section class="amendmentSupporters">
    <h3><?= Yii::t('admin', 'motion_type_initiator_amend') ?></h3>

    <div class="form-group">
        <label class="col-md-4 control-label" for="typeSupportTypeAmendment">
            <?= Yii::t('admin', 'motion_type_supp_form') ?>
        </label>
        <div class="col-md-8">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="type">
            <?php
            $options = [];
            foreach (SupportBase::getImplementations() as $formId => $formClass) {
                $supporters = ($formClass::hasInitiatorGivenSupporters() || $formClass === CollectBeforePublish::class);
                $options[]  = [
                    'title'      => $formClass::getTitle(),
                    'attributes' => ['data-has-supporters' => ($supporters ? '1' : '0')],
                ];
            }
            echo HTMLTools::fueluxSelectbox(
                'amendmentInitiatorSettings[type]',
                $options,
                $amendmentSettings->type,
                ['id' => 'typeSupportTypeAmendment'],
                true
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_person_type') ?>
        </div>
        <div class="col-md-8 contactDetails personTypes">
            <div class="form-control">
                <label>
                    <?php
                    echo Html::checkbox('amendmentInitiatorCanBePerson', $amendmentSettings->initiatorCanBePerson);
                    echo Yii::t('admin', 'motion_type_person_natural');
                    ?>
                </label>
                <label>
                    <?php
                    echo Html::checkbox('amendmentInitiatorCanBeOrganization', $amendmentSettings->initiatorCanBeOrganization);
                    echo Yii::t('admin', 'motion_type_person_orga');
                    ?>
                </label>
            </div>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_contact_name') ?>
        </div>
        <div class="col-md-8 contactDetails contactName">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="contactName">
            <?php
            echo Html::radioList(
                'amendmentInitiatorSettings[contactName]',
                $amendmentSettings->contactName,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_email') ?>
        </div>
        <div class="col-md-8 contactDetails contactEMail">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="contactEmail">
            <?php
            echo Html::radioList(
                'amendmentInitiatorSettings[contactEmail]',
                $amendmentSettings->contactEmail,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_phone') ?>
        </div>
        <div class="col-md-8 contactDetails contactPhone">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="contactPhone">
            <?php
            echo Html::radioList(
                'amendmentInitiatorSettings[contactPhone]',
                $amendmentSettings->contactPhone,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group formGroupResolutionDate">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_orga_resolution') ?>
        </div>
        <div class="col-md-8 contactDetails contactResolutionDate">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="hasResolutionDate">
            <?php
            echo Html::radioList(
                'amendmentInitiatorSettings[hasResolutionDate]',
                $amendmentSettings->hasResolutionDate,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group formGroupGender">
        <div class="col-md-4 control-label">
            <?= Yii::t('admin', 'motion_type_gender') ?>
        </div>
        <div class="col-md-8 contactDetails contactGender">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="contactGender">
            <?php
            echo Html::radioList(
                'amendmentInitiatorSettings[contactGender]',
                $amendmentSettings->contactGender,
                [
                    InitiatorForm::CONTACT_NONE     => Yii::t('admin', 'motion_type_skip'),
                    InitiatorForm::CONTACT_OPTIONAL => Yii::t('admin', 'motion_type_optional'),
                    InitiatorForm::CONTACT_REQUIRED => Yii::t('admin', 'motion_type_required'),
                ],
                ['class' => 'form-control']
            );
            ?>
        </div>
    </div>

    <div class="form-group" id="typeMinSupportersRowAmendment">
        <label class="col-md-4 control-label" for="typeMinSupportersAmendment">
            <?= Yii::t('admin', 'motion_type_supp_min') ?>
        </label>
        <div class="col-md-2">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="minSupporters">
            <input type="number" name="amendmentInitiatorSettings[minSupporters]" class="form-control" id="typeMinSupportersAmendment"
                   value="<?= Html::encode($amendmentSettings->minSupporters) ?>">
        </div>
    </div>

    <div class="form-group formGroupMinFemale" id="typeMinSupportersFemaleRowAmendment">
        <label class="col-md-4 control-label" for="typeMinSupportersFemaleAmendment">
            <?= Yii::t('admin', 'motion_type_supp_female_min') ?>
        </label>
        <div class="col-md-2">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="minSupportersFemale">
            <input type="number" name="amendmentInitiatorSettings[minSupportersFemale]" class="form-control" id="typeMinSupportersFemaleAmendment"
                   value="<?= Html::encode($amendmentSettings->minSupportersFemale) ?>">
        </div>
    </div>

    <div class="form-group" id="typeAllowMoreSupportersAmendment">
        <div class="checkbox col-md-8 col-md-offset-4">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="allowMoreSupporters">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'amendmentInitiatorSettings[allowMoreSupporters]',
                Yii::t('admin', 'motion_type_allow_more_supp'),
                $amendmentSettings->allowMoreSupporters
            );
            ?>
        </div>
    </div>

    <div class="form-group" id="typeHasOrgaRowAmendment">
        <div class="checkbox col-md-8 col-md-offset-4">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="hasOrganizations">
            <?php
            echo HTMLTools::fueluxCheckbox(
                'amendmentInitiatorSettings[hasOrganizations]',
                Yii::t('admin', 'motion_type_ask_orga'),
                $amendmentSettings->hasOrganizations
            );
            ?>
        </div>
    </div>
</section>
