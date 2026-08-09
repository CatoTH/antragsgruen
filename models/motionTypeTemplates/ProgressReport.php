<?php

declare(strict_types=1);

namespace app\models\motionTypeTemplates;

use app\views\pdfLayouts\IPDFLayout;
use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection};
use app\models\settings\{InitiatorForm, MotionType};
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\supportTypes\SupportBase;

class ProgressReport
{
    public static function doCreateProgressType(Consultation $consultation): ConsultationMotionType
    {
        $type                               = new ConsultationMotionType();
        $type->consultationId               = $consultation->id;
        $type->position                     = 0;
        $type->amendmentsOnly               = 0;
        $type->policyMotions                = (string)IPolicy::POLICY_ADMINS;
        $type->policyAmendments             = (string)IPolicy::POLICY_NOBODY;
        $type->policyComments               = (string)IPolicy::POLICY_NOBODY;
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
        $initiatorSettings->type         = SupportBase::ONLY_INITIATOR;
        $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactPhone = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactEmail = InitiatorForm::CONTACT_NONE;
        $type->supportTypeMotions        = json_encode($initiatorSettings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $type->supportTypeAmendments     = null;

        $type->setSettingsObj(new MotionType(null));

        (new MotionTypeTranslationHelper($type))->setLabels('preset_progress_singular', 'preset_progress_plural', 'preset_progress_call');

        $type->save();

        return $type;
    }

    public static function doCreateProgressSections(ConsultationMotionType $motionType): void
    {
        $builder = new MotionTypeTranslationHelper($motionType);

        $builder->addSection(function (?string $language): ConsultationSettingsMotionSection {
            $section                = new ConsultationSettingsMotionSection();
            $section->type          = ISectionType::TYPE_TITLE;
            $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
            $section->title         = \Yii::t('structure', 'preset_progress_title', [], $language);
            $section->required      = ConsultationSettingsMotionSection::REQUIRED_YES;
            $section->maxLen        = 0;
            $section->fixedWidth    = 0;
            $section->lineNumbers   = 0;
            $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
            $section->hasAmendments = 1;
            $section->positionRight = 0;
            $section->settings      = null;
            return $section;
        }, true, 'title');

        $builder->addSection(function (?string $language): ConsultationSettingsMotionSection {
            $section                = new ConsultationSettingsMotionSection();
            $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
            $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
            $section->title         = \Yii::t('structure', 'preset_progress_text', [], $language);
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

        $builder->addSection(function (?string $language): ConsultationSettingsMotionSection {
            $section                = new ConsultationSettingsMotionSection();
            $section->type          = ISectionType::TYPE_TEXT_EDITORIAL;
            $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
            $section->title         = \Yii::t('structure', 'preset_progress_report', [], $language);
            $section->required      = ConsultationSettingsMotionSection::REQUIRED_NO;
            $section->maxLen        = 0;
            $section->fixedWidth    = 0;
            $section->lineNumbers   = 0;
            $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
            $section->hasAmendments = 0;
            $section->positionRight = 0;
            $section->settings      = null;
            return $section;
        }, true, 'report');
    }
}
