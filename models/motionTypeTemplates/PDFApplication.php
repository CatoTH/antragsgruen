<?php

namespace app\models\motionTypeTemplates;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\settings\InitiatorForm;
use app\models\settings\MotionType;
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
        $type->supportType                  = SupportBase::ONLY_INITIATOR;
        $type->amendmentMultipleParagraphs  = 0;
        $type->amendAmendments              = 0;
        $type->motionLikesDislikes          = 0;
        $type->amendmentLikesDislikes       = 0;
        $type->status                       = ConsultationMotionType::STATUS_VISIBLE;
        $type->sidebarCreateButton          = 0;

        $initiatorSettings               = new InitiatorForm(null);
        $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
        $initiatorSettings->contactEmail = InitiatorForm::CONTACT_REQUIRED;
        $type->supportTypeSettings       = json_encode($initiatorSettings, JSON_PRETTY_PRINT);

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
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_PDF;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_pdf');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->positionRight = 0;
        $section->save();
    }
}
