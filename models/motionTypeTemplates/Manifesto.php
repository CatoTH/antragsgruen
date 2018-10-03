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

trait Manifesto
{
    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     * @throws \Exception
     */
    public static function doCreateManifestoType(Consultation $consultation)
    {
        $type                               = new ConsultationMotionType();
        $type->consultationId               = $consultation->id;
        $type->titleSingular                = \Yii::t('structure', 'preset_manifesto_singular');
        $type->titlePlural                  = \Yii::t('structure', 'preset_manifesto_plural');
        $type->createTitle                  = \Yii::t('structure', 'preset_manifesto_call');
        $type->position                     = 0;
        $type->policyMotions                = IPolicy::POLICY_ADMINS;
        $type->policyAmendments             = IPolicy::POLICY_ALL;
        $type->policyComments               = IPolicy::POLICY_ALL;
        $type->policySupportMotions         = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments      = IPolicy::POLICY_NOBODY;
        $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
        $type->supportType                  = SupportBase::ONLY_INITIATOR;
        $type->texTemplateId                = 1;
        $type->amendmentMultipleParagraphs  = 1;
        $type->motionLikesDislikes          = 0;
        $type->amendmentLikesDislikes       = 0;
        $type->status                       = ConsultationMotionType::STATUS_VISIBLE;
        $type->sidebarCreateButton          = 1;

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
    public static function doCreateManifestoSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_manifesto_title');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_manifesto_text');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 1;
        $section->lineNumbers   = 1;
        $section->hasComments   = 1;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->save();
    }
}
