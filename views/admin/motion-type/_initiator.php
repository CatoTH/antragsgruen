<?php

use app\components\HTMLTools;
use app\models\db\ConsultationMotionType;
use app\models\policies\IPolicy;
use app\models\settings\InitiatorForm;
use app\models\supportTypes\{CollectBeforePublish, SupportBase};
use yii\helpers\Html;

/**
 * @var ConsultationMotionType $motionType
 */

$motionSettings    = $motionType->getMotionSupportTypeClass()->getSettingsObj();
$amendmentSettings = $motionType->getAmendmentSupportTypeClass()->getSettingsObj();

$policies = [];
foreach (IPolicy::getPolicies() as $policy) {
    $policies[$policy::getPolicyID()] = $policy::getPolicyName();
}

$sameInitiatorSettingsForAmendments = (json_encode($motionSettings) === json_encode($amendmentSettings));
?>
<section class="motionSupporters hideForAmendmentsOnly" id="motionSupportersForm" aria-labelledby="motionSupportersFormTitle">
    <h2 class="green" id="motionSupportersFormTitle"><?= Yii::t('admin', 'motion_type_initiator') ?></h2>
    <div class="content">
    <div class="stdTwoCols">
        <label class="leftColumn" for="typeSupportType">
            <?= Yii::t('admin', 'motion_type_supp_form') ?>:
        </label>
        <div class="rightColumn">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="type">
            <select name="motionInitiatorSettings[type]" id="typeSupportType" class="supportType stdDropdown">
                <?php
                foreach (SupportBase::getImplementations() as $formId => $formClass) {
                    $supporters = ($formClass::hasInitiatorGivenSupporters() || $formClass === CollectBeforePublish::class);
                    echo '<option value="' . $formId . '" data-has-supporters="' . ($supporters ? '1' : '0') . '"';
                    if ($motionSettings->type === $formId) {
                        echo ' selected';
                    }
                    echo '>' . Html::encode($formClass::getTitle()) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_person_type') ?>:
        </div>
        <div class="rightColumn contactDetails personTypes">
            <div class="form-control">
                <label class="initiatorCanBePerson">
                    <?php
                    echo Html::checkbox('initiatorCanBePerson', $motionSettings->initiatorCanBePerson);
                    echo Yii::t('admin', 'motion_type_person_natural');
                    ?>
                </label>
                <label class="initiatorCanBeOrganization">
                    <?php
                    echo Html::checkbox('initiatorCanBeOrganization', $motionSettings->initiatorCanBeOrganization);
                    echo Yii::t('admin', 'motion_type_person_orga');
                    ?>
                </label>
                <label class="initiatorSetPermissions">
                    <?php
                    $isPermSet = (!is_a($motionSettings->getInitiatorPersonPolicy($motionType->getConsultation()),  \app\models\policies\All::class) ||
                                  !is_a($motionSettings->getInitiatorOrganizationPolicy($motionType->getConsultation()),  \app\models\policies\All::class));
                    echo Html::checkbox('type[initiatorSetPermissions]', $isPermSet, ['class' => 'hidden']);
                    ?>
                    <span class="btn btn-link">
                        <span class="glyphicon glyphicon-chevron-up active" aria-hidden="true"></span>
                        <span class="glyphicon glyphicon-chevron-down inactive" aria-hidden="true"></span>
                        <?= Yii::t('admin', 'motion_type_person_restrict') ?>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="initiatorSetPersonPermissions">
        <label class="leftColumn" for="typeInitiatorPersonPolicy">
            <?= Yii::t('admin', 'motion_type_policy_person') ?>:
        </label>
        <div class="rightColumn policyWidget policyWidgetPerson">
            <?php
            $currentPolicy = $motionSettings->getInitiatorPersonPolicy($motionType->getConsultation());
            echo Html::dropDownList(
                'type[initiatorPersonPolicy][id]',
                $currentPolicy::getPolicyID(),
                $policies,
                ['id' => 'typeInitiatorPersonPolicy', 'class' => 'stdDropdown policySelect']
            );
            echo $this->render('@app/views/shared/usergroup_selector', ['id' => 'typeInitiatorPersonGroups', 'formName' => 'type[initiatorPersonPolicy]', 'consultation' => $motionType->getConsultation(), 'currentPolicy' => $currentPolicy]);
            ?>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="initiatorSetOrgaPermissions">
        <label class="leftColumn" for="typeInitiatorOrgaPolicy">
            <?= Yii::t('admin', 'motion_type_policy_orga') ?>:
        </label>
        <div class="rightColumn policyWidget policyWidgetOrga">
            <?php
            $currentPolicy = $motionSettings->getInitiatorOrganizationPolicy($motionType->getConsultation());
            echo Html::dropDownList(
                'type[initiatorOrgaPolicy][id]',
                $currentPolicy::getPolicyID(),
                $policies,
                ['id' => 'typeInitiatorOrgaPolicy', 'class' => 'stdDropdown policySelect']
            );
            echo $this->render('@app/views/shared/usergroup_selector', ['id' => 'typeInitiatorOrgaGroups', 'formName' => 'type[initiatorOrgaPolicy]', 'consultation' => $motionType->getConsultation(), 'currentPolicy' => $currentPolicy]);
            ?>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_contact_name') ?>:
        </div>
        <div class="rightColumn contactDetails contactName">
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

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_email') ?>:
        </div>
        <div class="rightColumn contactDetails contactEMail">
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

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_phone') ?>:
        </div>
        <div class="rightColumn contactDetails contactPhone">
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

    <div class="stdTwoCols formGroupResolutionDate" data-visibility="initiatorCanBeOrga">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_orga_resolution') ?>:
        </div>
        <div class="rightColumn contactDetails contactResolutionDate">
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

    <div class="stdTwoCols formGroupGender" data-visibility="initiatorCanBePerson">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_gender') ?>:
        </div>
        <div class="rightColumn contactDetails contactGender">
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

    <div class="stdTwoCols formGroupMinSupporters" id="typeMinSupportersRow" data-visibility="hasSupporters">
        <label class="leftColumn" for="typeMinSupporters">
            <?= Yii::t('admin', 'motion_type_supp_min') ?>:
        </label>
        <div class="rightColumn">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="minSupporters">
            <input type="number" name="motionInitiatorSettings[minSupporters]" class="form-control" id="typeMinSupporters"
                   value="<?= Html::encode($motionSettings->minSupporters) ?>">
        </div>
    </div>

    <div class="stdTwoCols formGroupMinFemale" id="typeMinSupportersFemaleRow" data-visibility="allowFemaleQuota">
        <label class="leftColumn control-label" for="typeMinSupportersFemale">
            <?= Yii::t('admin', 'motion_type_supp_female_min') ?>:
            <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_supp_female_h')) ?>
        </label>
        <div class="rightColumn">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="minSupportersFemale">
            <input type="number" name="motionInitiatorSettings[minSupportersFemale]" class="form-control" id="typeMinSupportersFemale"
                   value="<?= Html::encode($motionSettings->minSupportersFemale ?: '') ?>">
        </div>
    </div>

    <div class="stdTwoCols formGroupAllowMore" data-visibility="hasSupporters">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="allowMoreSupporters">
            <?php
            echo HTMLTools::labeledCheckbox(
                'motionInitiatorSettings[allowMoreSupporters]',
                Yii::t('admin', 'motion_type_allow_more_supp'),
                $motionSettings->allowMoreSupporters,
                'typeAllowMoreSupporters'
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols formGroupAllowAfterPub" data-visibility="allowSupportAfterSubmission">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="allowSupportingAfterPublication">
            <?php
            echo HTMLTools::labeledCheckbox(
                'motionInitiatorSettings[allowSupportingAfterPublication]',
                Yii::t('admin', 'motion_type_supp_after_pub'),
                $motionSettings->allowSupportingAfterPublication,
                'typeAllowSupportingAfterPublication',
                Yii::t('admin', 'motion_type_supp_after_pubh')
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols formGroupOfferNonPublic" data-visibility="isCollectingSupporters">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="offerNonPublicSupports">
            <?php
            echo HTMLTools::labeledCheckbox(
                'motionInitiatorSettings[offerNonPublicSupports]',
                Yii::t('admin', 'motion_type_nonpublicsupp_pub'),
                $motionSettings->offerNonPublicSupports,
                'typeOfferNonPublicSupports',
                Yii::t('admin', 'motion_type_nonpublicsupp_pubh')
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols" id="typeHasOrgaRow" data-visibility="hasInitiator">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="motionInitiatorSettingFields[]" value="hasOrganizations">
            <?php
            echo HTMLTools::labeledCheckbox(
                'motionInitiatorSettings[hasOrganizations]',
                Yii::t('admin', 'motion_type_ask_orga'),
                $motionSettings->hasOrganizations,
                'typeHasOrga'
            );
            ?>
        </div>
    </div>


    <div class="stdTwoCols" id="sameInitiatorSettingsForAmendments">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <?php
            echo HTMLTools::labeledCheckbox(
                'sameInitiatorSettingsForAmendments',
                Yii::t('admin', 'motion_type_same_amendment'),
                $sameInitiatorSettingsForAmendments
            );
            ?>
        </div>
    </div>
    </div>
</section>


<section class="amendmentSupporters" id="amendmentSupportersForm">
    <h2 class="green"><?= Yii::t('admin', 'motion_type_initiator_amend') ?></h2>
    <div class="content">
    <div class="stdTwoCols">
        <label class="leftColumn" for="typeSupportTypeAmendment">
            <?= Yii::t('admin', 'motion_type_supp_form') ?>:
        </label>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="type">
            <select name="amendmentInitiatorSettings[type]" id="typeSupportTypeAmendment" class="supportType stdDropdown">
                <?php
                foreach (SupportBase::getImplementations() as $formId => $formClass) {
                    $supporters = ($formClass::hasInitiatorGivenSupporters() || $formClass === CollectBeforePublish::class);
                    echo '<option value="' . $formId . '" data-has-supporters="' . ($supporters ? '1' : '0') . '"';
                    if ($amendmentSettings->type === $formId) {
                        echo ' selected';
                    }
                    echo '>' . Html::encode($formClass::getTitle()) . '</option>';
                }
                ?>
            </select>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_person_type') ?>:
        </div>
        <div class="rightColumn contactDetails personTypes">
            <div class="form-control">
                <label class="initiatorCanBePerson">
                    <?php
                    echo Html::checkbox('amendmentInitiatorCanBePerson', $amendmentSettings->initiatorCanBePerson);
                    echo Yii::t('admin', 'motion_type_person_natural');
                    ?>
                </label>
                <label class="initiatorCanBeOrganization">
                    <?php
                    echo Html::checkbox('amendmentInitiatorCanBeOrganization', $amendmentSettings->initiatorCanBeOrganization);
                    echo Yii::t('admin', 'motion_type_person_orga');
                    ?>
                </label>
                <label class="initiatorSetPermissions">
                    <?php
                    $isPermSet = (!is_a($amendmentSettings->getInitiatorPersonPolicy($motionType->getConsultation()),  \app\models\policies\All::class) ||
                                  !is_a($amendmentSettings->getInitiatorOrganizationPolicy($motionType->getConsultation()),  \app\models\policies\All::class));
                    echo Html::checkbox('type[amendmentInitiatorSetPermissions]', $isPermSet, ['class' => 'hidden']);
                    ?>
                    <span class="btn btn-link">
                        <span class="glyphicon glyphicon-chevron-up active" aria-hidden="true"></span>
                        <span class="glyphicon glyphicon-chevron-down inactive" aria-hidden="true"></span>
                        <?= Yii::t('admin', 'motion_type_person_restrict') ?>
                    </span>
                </label>
            </div>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="initiatorSetPersonPermissions">
        <label class="leftColumn" for="typeAmendmentInitiatorPersonPolicy">
            <?= Yii::t('admin', 'motion_type_policy_person') ?>:
        </label>
        <div class="rightColumn policyWidget policyWidgetPerson">
            <?php
            $currentPolicy = $amendmentSettings->getInitiatorPersonPolicy($motionType->getConsultation());
            echo Html::dropDownList(
                'type[amendmentInitiatorPersonPolicy][id]',
                $currentPolicy::getPolicyID(),
                $policies,
                ['id' => 'typeAmendmentInitiatorPersonPolicy', 'class' => 'stdDropdown policySelect']
            );
            echo $this->render('@app/views/shared/usergroup_selector', ['id' => 'typeAmendmentInitiatorPersonGroups', 'formName' => 'type[amendmentInitiatorPersonPolicy]', 'consultation' => $motionType->getConsultation(), 'currentPolicy' => $currentPolicy]);
            ?>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="initiatorSetOrgaPermissions">
        <label class="leftColumn" for="typeAmendmentInitiatorOrgaPolicy">
            <?= Yii::t('admin', 'motion_type_policy_orga') ?>:
        </label>
        <div class="rightColumn policyWidget policyWidgetOrga">
            <?php
            $currentPolicy = $amendmentSettings->getInitiatorOrganizationPolicy($motionType->getConsultation());
            echo Html::dropDownList(
                'type[amendmentInitiatorOrgaPolicy][id]',
                $currentPolicy::getPolicyID(),
                $policies,
                ['id' => 'typeAmendmentInitiatorOrgaPolicy', 'class' => 'stdDropdown policySelect']
            );
            echo $this->render('@app/views/shared/usergroup_selector', ['id' => 'typeAmendmentInitiatorOrgaGroups', 'formName' => 'type[amendmentInitiatorOrgaPolicy]', 'consultation' => $motionType->getConsultation(), 'currentPolicy' => $currentPolicy]);
            ?>
        </div>
    </div>

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_contact_name') ?>:
        </div>
        <div class="rightColumn contactDetails contactName">
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

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_email') ?>
        </div>
        <div class="rightColumn contactDetails contactEMail">
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

    <div class="stdTwoCols" data-visibility="hasInitiator">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_phone') ?>
        </div>
        <div class="rightColumn contactDetails contactPhone">
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

    <div class="stdTwoCols formGroupResolutionDate" data-visibility="initiatorCanBeOrga">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_orga_resolution') ?>:
        </div>
        <div class="rightColumn contactDetails contactResolutionDate">
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

    <div class="stdTwoCols formGroupGender" data-visibility="initiatorCanBePerson">
        <div class="leftColumn">
            <?= Yii::t('admin', 'motion_type_gender') ?>:
        </div>
        <div class="rightColumn contactDetails contactGender">
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

    <div class="stdTwoCols formGroupMinSupporters" id="typeMinSupportersRowAmendment" data-visibility="hasSupporters">
        <label class="leftColumn" for="typeMinSupportersAmendment">
            <?= Yii::t('admin', 'motion_type_supp_min') ?>:
        </label>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="minSupporters">
            <input type="number" name="amendmentInitiatorSettings[minSupporters]" class="form-control" id="typeMinSupportersAmendment"
                   value="<?= Html::encode($amendmentSettings->minSupporters) ?>">
        </div>
    </div>

    <div class="stdTwoCols formGroupMinFemale" id="typeMinSupportersFemaleRowAmendment" data-visibility="allowFemaleQuota">
        <label class="leftColumn" for="typeMinSupportersFemaleAmendment">
            <?= Yii::t('admin', 'motion_type_supp_female_min') ?>:
            <?= HTMLTools::getTooltipIcon(Yii::t('admin', 'motion_type_supp_female_h')) ?>
        </label>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="minSupportersFemale">
            <input type="number" name="amendmentInitiatorSettings[minSupportersFemale]" class="form-control" id="typeMinSupportersFemaleAmendment"
                   value="<?= Html::encode($amendmentSettings->minSupportersFemale ?: '') ?>">
        </div>
    </div>

    <div class="stdTwoCols formGroupAllowMore" data-visibility="hasSupporters">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="allowMoreSupporters">
            <?php
            echo HTMLTools::labeledCheckbox(
                'amendmentInitiatorSettings[allowMoreSupporters]',
                Yii::t('admin', 'motion_type_allow_more_supp'),
                $amendmentSettings->allowMoreSupporters,
                'typeAllowMoreSupportersAmendment'
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols formGroupAllowAfterPub" data-visibility="allowSupportAfterSubmission">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="allowSupportingAfterPublication">
            <?php
            echo HTMLTools::labeledCheckbox(
                'amendmentInitiatorSettings[allowSupportingAfterPublication]',
                Yii::t('admin', 'motion_type_supp_after_pub'),
                $amendmentSettings->allowSupportingAfterPublication,
                'typeAllowSupportingAfterPublicationAmendment',
                Yii::t('admin', 'motion_type_supp_after_pubh')
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols formGroupOfferNonPublic" data-visibility="isCollectingSupporters">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="offerNonPublicSupports">
            <?php
            echo HTMLTools::labeledCheckbox(
                'amendmentInitiatorSettings[offerNonPublicSupports]',
                Yii::t('admin', 'motion_type_nonpublicsupp_pub'),
                $amendmentSettings->offerNonPublicSupports,
                'typeOfferNonPublicSupportsAmendment',
                Yii::t('admin', 'motion_type_nonpublicsupp_pubh')
            );
            ?>
        </div>
    </div>

    <div class="stdTwoCols" id="typeHasOrgaRowAmendment" data-visibility="hasInitiator">
        <div class="leftColumn"></div>
        <div class="rightColumn">
            <input type="hidden" name="amendmentInitiatorSettingFields[]" value="hasOrganizations">
            <?php
            echo HTMLTools::labeledCheckbox(
                'amendmentInitiatorSettings[hasOrganizations]',
                Yii::t('admin', 'motion_type_ask_orga'),
                $amendmentSettings->hasOrganizations,
                'typeHasOrgaAmendment'
            );
            ?>
        </div>
    </div>
    </div>
</section>
