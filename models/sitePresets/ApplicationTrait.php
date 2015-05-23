<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationMotionType;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TabularDataType;

trait ApplicationTrait
{
    /**
     * @param Consultation $consultation
     * @return ConsultationMotionType
     */
    public function doCreateApplicationType(Consultation $consultation)
    {
        $type                   = new ConsultationMotionType();
        $type->consultationId   = $consultation->id;
        $type->titleSingular    = 'Bewerbung';
        $type->titlePlural      = 'Bewerbungen';
        $type->createTitle      = 'Bewerben';
        $type->position         = 0;
        $type->policyMotions    = IPolicy::POLICY_ALL;
        $type->policyAmendments = IPolicy::POLICY_NOBODY;
        $type->policyComments   = IPolicy::POLICY_NOBODY;
        $type->policySupport    = IPolicy::POLICY_LOGGED_IN;
        $type->save();

        return $type;
    }

    /**
     * @param ConsultationMotionType $motionType
     */
    public function doCreateApplicationSections(ConsultationMotionType $motionType)
    {
        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TITLE;
        $section->position      = 0;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = 'Name';
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_IMAGE;
        $section->position      = 1;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = 'Foto';
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TABULAR;
        $section->position      = 2;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = 'Angaben';
        $section->required      = 0;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->data          = json_encode(
            [
                'maxRowId' => 2,
                'rows'     => [
                    '1' => new TabularDataType(
                        [
                            'rowId' => 1,
                            'title' => 'Alter',
                            'type'  => TabularDataType::TYPE_INTEGER,
                        ]
                    ),
                    '2' => new TabularDataType(
                        [
                            'rowId' => 2,
                            'title' => 'Geschlecht',
                            'type'  => TabularDataType::TYPE_STRING,
                        ]
                    ),
                    '3' => new TabularDataType(
                        [
                            'rowId' => 3,
                            'title' => 'Geburtsort',
                            'type'  => TabularDataType::TYPE_STRING,
                        ]
                    ),
                ],
            ]
        );
        $section->save();

        $section                = new ConsultationSettingsMotionSection();
        $section->motionTypeId  = $motionType->id;
        $section->type          = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position      = 3;
        $section->status        = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title         = 'Selbstvorstellung';
        $section->required      = 1;
        $section->maxLen        = 0;
        $section->fixedWidth    = 0;
        $section->lineNumbers   = 0;
        $section->hasComments   = 0;
        $section->hasAmendments = 0;
        $section->save();
    }
}
