<?php

declare(strict_types=1);

namespace app\models\motionTypeTemplates;

use app\views\pdfLayouts\IPDFLayout;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection};
use app\models\settings\{InitiatorForm, MotionType};
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;

class Statutes
{
    public static function doCreateStatutesType(Consultation $consultation): ConsultationMotionType
    {
        $type                               = new ConsultationMotionType();
        $type->consultationId               = $consultation->id;
        $type->motionPrefix                 = 'S';
        $type->position                     = 0;
        $type->amendmentsOnly               = 1;
        $type->policyMotions                = (string)IPolicy::POLICY_ADMINS;
        $type->policyAmendments             = (string)IPolicy::POLICY_ALL;
        $type->policyComments               = (string)IPolicy::POLICY_ALL;
        $type->policySupportMotions         = (string)IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments      = (string)IPolicy::POLICY_NOBODY;
        $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
        $type->texTemplateId                = null;
        $type->amendmentMultipleParagraphs  = ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE;
        $type->motionLikesDislikes          = 0;
        $type->amendmentLikesDislikes       = 0;
        $type->status                       = ConsultationMotionType::STATUS_VISIBLE;
        $type->sidebarCreateButton          = 0;
        $type->pdfLayout                    = IPDFLayout::getDefaultLayoutId();

        $initiatorSettings               = new InitiatorForm(null);
        $initiatorSettings->type         = SupportBase::NO_INITIATOR;
        $type->supportTypeMotions        = json_encode($initiatorSettings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $initiatorSettings               = new InitiatorForm(null);
        $initiatorSettings->type         = SupportBase::ONLY_INITIATOR;
        $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
        $initiatorSettings->contactEmail = InitiatorForm::CONTACT_REQUIRED;
        $type->supportTypeAmendments     = json_encode($initiatorSettings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);

        $type->setSettingsObj(new MotionType(null));

        (new MotionTypeTranslationHelper($type))->setLabels('preset_statutes_singular', 'preset_statutes_plural', 'preset_statutes_call');

        $type->save();

        return $type;
    }

    public static function doCreateStatutesSections(ConsultationMotionType $motionType): void
    {
        $helper = new MotionTypeTranslationHelper($motionType);

        $helper->addSection(function (?string $language): ConsultationSettingsMotionSection {
            $section                = new ConsultationSettingsMotionSection();
            $section->type          = ISectionType::TYPE_TITLE;
            $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
            $section->title         = \Yii::t('structure', 'preset_statutes_title', [], $language);
            $section->required      = ConsultationSettingsMotionSection::REQUIRED_YES;
            $section->maxLen        = 0;
            $section->fixedWidth    = 0;
            $section->lineNumbers   = 0;
            $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
            $section->hasAmendments = 0;
            $section->positionRight = 0;
            $section->settings      = null;
            return $section;
        }, true, 'title');

        $helper->addSection(function (?string $language): ConsultationSettingsMotionSection {
            $section                = new ConsultationSettingsMotionSection();
            $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
            $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
            $section->title         = \Yii::t('structure', 'preset_statutes_text', [], $language);
            $section->required      = ConsultationSettingsMotionSection::REQUIRED_YES;
            $section->maxLen        = 0;
            $section->fixedWidth    = 1;
            $section->lineNumbers   = 1;
            $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_MOTION;
            $section->hasAmendments = 1;
            $section->positionRight = 0;
            $section->settings      = null;
            return $section;
        }, true, 'text');
    }
}
