<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;

trait MotionTrait
{
    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     */
    public function doCreateMotionType(Consultation $consultation)
    {
        $type                   = new ConsultationMotionType();
        $type->consultationId   = $consultation->id;
        $type->titleSingular    = 'Antrag';
        $type->titlePlural      = 'Anträge';
        $type->createTitle      = 'Antrag stellen';
        $type->position         = 0;
        $type->policyMotions    = IPolicy::POLICY_ALL;
        $type->policyAmendments = IPolicy::POLICY_ALL;
        $type->policyComments   = IPolicy::POLICY_ALL;
        $type->policySupport    = IPolicy::POLICY_LOGGED_IN;
        $type->contactPhone     = ConsultationMotionType::CONTACT_OPTIONAL;
        $type->contactEmail     = ConsultationMotionType::CONTACT_REQUIRED;
        $type->save();

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    public function doCreateMotionSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = 'Titel';
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
        $section->title         = 'Antragstext';
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
        $section->title         = 'Begründung';
        $section->required      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->save();
    }
}
