<?php

namespace app\models\sitePresets;


use app\models\db\Consultation;
use app\models\db\ConsultationSettingsMotionSection;
use app\models\db\Site;
use app\models\policies\IPolicy;

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
        $section->type           = ConsultationSettingsMotionSection::TYPE_TEXT_PLAIN;
        $section->position       = 1;
        $section->title          = "Antragstext";
        $section->maxLen         = 0;
        $section->fixedWidth     = 1;
        $section->lineNumbers    = 1;
        $section->save();

        $section                 = new ConsultationSettingsMotionSection();
        $section->consultationId = $consultation->id;
        $section->type           = ConsultationSettingsMotionSection::TYPE_TEXT_PLAIN;
        $section->position       = 2;
        $section->title          = "Begründung";
        $section->maxLen         = 0;
        $section->fixedWidth     = 0;
        $section->lineNumbers    = 0;
        $section->save();
    }
}
