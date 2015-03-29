<?php

namespace app\models\sitePresets;


use app\models\db\Consultation;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\ConsultationSettingsMotionType;
use app\models\db\Site;
use app\models\policies\IPolicy;
use app\models\sectionTypes\ISectionType;

class Parteitag implements ISitePreset
{

    /**
     * @return string
     */
    public static function getTitle()
    {
        return "Parteitag";
    }

    /**
     * @return string
     */
    public static function getDescription()
    {
        return "irgendwas zum Parteitag";
    }

    /**
     * @param Consultation $consultation
     */
    public static function setConsultationSettings(Consultation $consultation)
    {
        $settings                       = $consultation->getSettings();
        $settings->lineNumberingGlobal  = false;
        $settings->amendNumberingGlobal = false;
        $settings->screeningMotions     = true;
        $settings->screeningAmendments  = true;

        $consultation->policyMotions    = IPolicy::POLICY_ALL;
        $consultation->policyAmendments = IPolicy::POLICY_ALL;
        $consultation->policyComments   = IPolicy::POLICY_ALL;
        $consultation->policySupport    = IPolicy::POLICY_ALL;
        $consultation->wording          = \app\models\wording\Parteitag::getWordingID();
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
        $section                 = new ConsultationSettingsMotionSection();
        $section->consultationId = $consultation->id;
        $section->type           = ISectionType::TYPE_TITLE;
        $section->position       = 0;
        $section->status         = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title          = 'Titel';
        $section->required       = 1;
        $section->maxLen         = 0;
        $section->fixedWidth     = 0;
        $section->lineNumbers    = 0;
        $section->hasComments    = 0;
        $section->save();

        $section                 = new ConsultationSettingsMotionSection();
        $section->consultationId = $consultation->id;
        $section->type           = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position       = 1;
        $section->status         = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title          = 'Antragstext';
        $section->required       = 1;
        $section->maxLen         = 0;
        $section->fixedWidth     = 1;
        $section->lineNumbers    = 1;
        $section->hasComments    = 1;
        $section->save();

        $section                 = new ConsultationSettingsMotionSection();
        $section->consultationId = $consultation->id;
        $section->type           = ISectionType::TYPE_TEXT_SIMPLE;
        $section->position       = 2;
        $section->status         = ConsultationSettingsMotionSection::STATUS_VISIBLE;
        $section->title          = 'Begründung';
        $section->required       = 0;
        $section->maxLen         = 0;
        $section->fixedWidth     = 0;
        $section->lineNumbers    = 0;
        $section->hasComments    = 0;
        $section->save();
    }

    /**
     * @param Consultation $consultation
     */
    public static function createMotionTypes(Consultation $consultation)
    {
        $type                 = new ConsultationSettingsMotionType();
        $type->consultationId = $consultation->id;
        $type->title          = 'Antrag';
        $type->position       = 0;
        $type->save();

        $type                 = new ConsultationSettingsMotionType();
        $type->consultationId = $consultation->id;
        $type->title          = 'Resolution';
        $type->position       = 1;
        $type->save();

        $type                 = new ConsultationSettingsMotionType();
        $type->consultationId = $consultation->id;
        $type->title          = 'Satzungsantrag';
        $type->position       = 2;
        $type->save();
    }
}
