<?php

namespace app\models\sitePresets;

use app\models\db\Consultation;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationSettingsMotionType;
use app\models\db\Site;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TabularDataType;

class Bewerbungsverfahren implements ISitePreset
{

    /**
     * @return string
     */
    public static function getTitle()
    {
        return "Bewertungsverfahren";
    }

    /**
     * @return string
     */
    public static function getDescription()
    {
        return "irgendwas zum Bewertungsverfahren";
    }

    /**
     * @return array
     */
    public static function getDetailDefaults()
    {
        return [
            'comments'   => false,
            'amendments' => false,
            'openNow'    => true
        ];
    }

    /**
     * @param Consultation $consultation
     */
    public static function setConsultationSettings(Consultation $consultation)
    {
        $settings                      = $consultation->getSettings();
        $settings->lineNumberingGlobal = false;
        $settings->screeningMotions    = false;
        $settings->screeningAmendments = false;

        $consultation->policyMotions    = IPolicy::POLICY_ALL;
        $consultation->policyAmendments = IPolicy::POLICY_NOBODY;
        $consultation->policyComments   = IPolicy::POLICY_NOBODY;
        $consultation->policySupport    = IPolicy::POLICY_LOGGED_IN;
        $consultation->wordingBase      = 'de-bewerbung';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param Site $site
     */
    public static function setSiteSettings(Site $site)
    {

    }

    /**
     * @param Consultation $consultation
     */
    public static function createMotionSections(Consultation $consultation)
    {
        $motionType = $consultation->motionTypes[0];

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

        $motionType->refresh();
    }

    /**
     * @param Consultation $consultation
     */
    public static function createMotionTypes(Consultation $consultation)
    {
        $type                 = new ConsultationSettingsMotionType();
        $type->consultationId = $consultation->id;
        $type->title          = 'Bewerbung';
        $type->position       = 0;
        $type->save();
        $consultation->refresh();
    }
}
