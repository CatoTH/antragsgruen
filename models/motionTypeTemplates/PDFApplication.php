<?php

namespace app\models\motionTypeTemplates;

use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection};
use app\models\settings\{InitiatorForm, MotionSection, MotionType};
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;

trait PDFApplication
{
    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     * @throws \Exception
     */
    public static function doCreateApplicationType(Consultation $consultation)
    {
        $type                               = new ConsultationMotionType();
        $type->consultationId               = $consultation->id;
        $type->titleSingular                = \Yii::t('structure', 'preset_app_singular');
        $type->titlePlural                  = \Yii::t('structure', 'preset_app_plural');
        $type->createTitle                  = \Yii::t('structure', 'preset_app_call');
        $type->position                     = 0;
        $type->policyMotions                = IPolicy::POLICY_ALL;
        $type->policyAmendments             = IPolicy::POLICY_NOBODY;
        $type->policyComments               = IPolicy::POLICY_NOBODY;
        $type->policySupportMotions         = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments      = IPolicy::POLICY_NOBODY;
        $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
        $type->amendmentMultipleParagraphs  = 0;
        $type->motionLikesDislikes          = 0;
        $type->amendmentLikesDislikes       = 0;
        $type->status                       = ConsultationMotionType::STATUS_VISIBLE;
        $type->sidebarCreateButton          = 0;

        $initiatorSettings               = new InitiatorForm(null);
        $initiatorSettings->type         = SupportBase::ONLY_INITIATOR;
        $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
        $initiatorSettings->contactEmail = InitiatorForm::CONTACT_REQUIRED;
        $type->supportTypeMotions        = json_encode($initiatorSettings, JSON_PRETTY_PRINT);
        $type->supportTypeAmendments     = null;
        $type->supportType               = 0; // @TODO Remove after database fields have been deleted
        $type->supportTypeSettings       = ''; // @TODO Remove after database fields have been deleted

        $type->setSettingsObj(new MotionType(null));

        $type->save();

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    public static function doCreateApplicationSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_name');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 0;
        $section->positionRight = 0;
        $section->settings      = null;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_PDF_ALTERNATIVE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_pdf');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 0;
        $section->positionRight = 0;

        $settings = new MotionSection(null);
        $settings->showInHtml = true;
        $section->setSettingsObj($settings);
        $section->save();
    }
}
