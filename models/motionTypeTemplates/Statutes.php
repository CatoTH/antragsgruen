<?php

namespace app\models\motionTypeTemplates;

use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection};
use app\models\settings\{AntragsgruenApp, InitiatorForm, MotionType};
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;

trait Statutes
{
    public static function doCreateStatutesType(Consultation $consultation): ConsultationMotionType
    {
        /** @var AntragsgruenApp $config */
        $config = \Yii::$app->params;

        $type                               = new ConsultationMotionType();
        $type->consultationId               = $consultation->id;
        $type->titleSingular                = \Yii::t('structure', 'preset_statutes_singular');
        $type->titlePlural                  = \Yii::t('structure', 'preset_statutes_plural');
        $type->createTitle                  = \Yii::t('structure', 'preset_statutes_call');
        $type->position                     = 0;
        $type->amendmentsOnly               = 1;
        $type->policyMotions                = IPolicy::POLICY_ADMINS;
        $type->policyAmendments             = IPolicy::POLICY_ALL;
        $type->policyComments               = IPolicy::POLICY_ALL;
        $type->policySupportMotions         = IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments      = IPolicy::POLICY_NOBODY;
        $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
        $type->texTemplateId                = ($config->xelatexPath || $config->lualatexPath ? 1 : null);
        $type->amendmentMultipleParagraphs  = 1;
        $type->motionLikesDislikes          = 0;
        $type->amendmentLikesDislikes       = 0;
        $type->status                       = ConsultationMotionType::STATUS_VISIBLE;
        $type->sidebarCreateButton          = 1;

        $initiatorSettings               = new InitiatorForm(null);
        $initiatorSettings->type         = SupportBase::ONLY_INITIATOR;
        $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
        $initiatorSettings->contactEmail = InitiatorForm::CONTACT_REQUIRED;
        $type->supportTypeMotions        = json_encode($initiatorSettings, JSON_PRETTY_PRINT);
        $type->supportTypeAmendments     = null;

        $type->setSettingsObj(new MotionType(null));

        $type->save();

        return $type;
    }

    public static function doCreateStatutesSections(ConsultationMotionType $motionType): void
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_statutes_title');
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
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_statutes_text');
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 1;
        $section->lineNumbers   = 1;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_MOTION;
        $section->hasAmendments = 1;
        $section->positionRight = 0;
        $section->settings      = null;
        $section->save();
    }
}
