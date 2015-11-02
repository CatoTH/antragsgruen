<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\initiatorForms\IInitiatorForm;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;

trait MotionTrait
{
    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     */
    public static function doCreateMotionType(Consultation $consultation)
    {
        $type                              = new ConsultationMotionType();
        $type->consultationId              = $consultation->id;
        $type->titleSingular               = \Yii::t('structure', 'preset_motion_singular');
        $type->titlePlural                 = \Yii::t('structure', 'preset_motion_plural');
        $type->createTitle                 = \Yii::t('structure', 'preset_motion_call');
        $type->position                    = 0;
        $type->policyMotions               = IPolicy::POLICY_ALL;
        $type->policyAmendments            = IPolicy::POLICY_ALL;
        $type->policyComments              = IPolicy::POLICY_ALL;
        $type->policySupport               = IPolicy::POLICY_NOBODY;
        $type->contactPhone                = ConsultationMotionType::CONTACT_OPTIONAL;
        $type->contactEmail                = ConsultationMotionType::CONTACT_REQUIRED;
        $type->initiatorForm               = IInitiatorForm::ONLY_INITIATOR;
        $type->texTemplateId               = 1;
        $type->amendmentMultipleParagraphs = 1;
        $type->save();

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    public static function doCreateMotionSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_title');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 1;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_text');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 1;
        $section->lineNumbers   = 1;
        $section->hasComments   = 1;
        $section->hasAmendments = 1;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 2;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_motion_reason');
        $section->required      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->save();
    }
}
