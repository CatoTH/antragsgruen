<?php

namespace app\models\motionTypeTemplates;

use app\models\db\{Consultation, ConsultationMotionType, ConsultationSettingsMotionSection};
use app\models\settings\{InitiatorForm, MotionType};
use app\models\policies\IPolicy;
use app\models\sectionTypes\{ISectionType, TabularDataType};
use app\models\supportTypes\SupportBase;

class Application
{
    public static function doCreateApplicationType(Consultation $consultation): ConsultationMotionType
    {
        $type                               = new ConsultationMotionType();
        $type->consultationId               = $consultation->id;
        $type->titleSingular                = \Yii::t('structure', 'preset_app_singular');
        $type->titlePlural                  = \Yii::t('structure', 'preset_app_plural');
        $type->createTitle                  = \Yii::t('structure', 'preset_app_call');
        $type->position                     = 0;
        $type->amendmentsOnly               = 0;
        $type->policyMotions                = (string)IPolicy::POLICY_ALL;
        $type->policyAmendments             = (string)IPolicy::POLICY_NOBODY;
        $type->policyComments               = (string)IPolicy::POLICY_NOBODY;
        $type->policySupportMotions         = (string)IPolicy::POLICY_NOBODY;
        $type->policySupportAmendments      = (string)IPolicy::POLICY_NOBODY;
        $type->initiatorsCanMergeAmendments = ConsultationMotionType::INITIATORS_MERGE_NEVER;
        $type->amendmentMultipleParagraphs  = ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_PARAGRAPH;
        $type->motionLikesDislikes          = 0;
        $type->amendmentLikesDislikes       = 0;
        $type->status                       = ConsultationMotionType::STATUS_VISIBLE;
        $type->sidebarCreateButton          = 0;

        $initiatorSettings               = new InitiatorForm(null);
        $initiatorSettings->type         = SupportBase::ONLY_INITIATOR;
        $initiatorSettings->contactName  = InitiatorForm::CONTACT_NONE;
        $initiatorSettings->contactPhone = InitiatorForm::CONTACT_OPTIONAL;
        $initiatorSettings->contactEmail = InitiatorForm::CONTACT_REQUIRED;
        $type->supportTypeMotions        = json_encode($initiatorSettings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $type->supportTypeAmendments     = null;

        $settings                   = new MotionType(null);
        $settings->motionTitleIntro = '';
        $type->setSettingsObj($settings);

        $type->save();

        return $type;
    }

    public static function doCreateApplicationSections(ConsultationMotionType $motionType): void
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_name');
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_YES;
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
        $section->type          = ISectionType::TYPE_IMAGE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_photo');
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_YES;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 0;
        $section->positionRight = 1;
        $section->settings      = null;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TABULAR;
        $section->position      = 2;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_data');
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_NO;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 0;
        $section->positionRight = 1;
        $section->settings      = null;
        $section->data          = json_encode(
            [
                'maxRowId' => 2,
                'rows'     => [
                    '1' => new TabularDataType(
                        [
                            'rowId' => '1',
                            'title' => \Yii::t('structure', 'preset_app_age'),
                            'type'  => TabularDataType::TYPE_INTEGER,
                        ]
                    ),
                    '4' => new TabularDataType(
                        [
                            'rowId' => '3',
                            'title' => \Yii::t('structure', 'preset_app_birthcity'),
                            'type'  => TabularDataType::TYPE_STRING,
                        ]
                    ),
                ],
            ],
            JSON_THROW_ON_ERROR
        );
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 3;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_intro');
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_YES;
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
        $section->type          = ISectionType::TYPE_IMAGE;
        $section->position      = 4;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = \Yii::t('structure', 'preset_app_signature');
        $section->required      = ConsultationSettingsMotionSection::REQUIRED_NO;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = ConsultationSettingsMotionSection::COMMENTS_NONE;
        $section->hasAmendments = 0;
        $section->positionRight = 0;
        $settings = $section->getSettingsObj();
        $settings->imgMaxWidth  = 5;
        $settings->imgMaxHeight = 3;
        $section->setSettingsObj($settings);
        $section->save();
    }
}
