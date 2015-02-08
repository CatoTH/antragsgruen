<?php

namespace app\models\sitePresets;


use app\models\db\Consultation;
use app\models\db\Site;

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
    public function setConsultationSettings(Consultation $consultation)
    {
        $settings                       = $consultation->getSettings();
        $settings->lineNumberingGlobal  = false;
        $settings->amendNumberingGlobal = false;
        $settings->screeningMotions     = true;
        $settings->screeningAmendments  = true;

        $consultation->policyMotions    = IPolicyAntraege::$POLICY_ALLE;
        $consultation->policyAmendments = IPolicyAntraege::$POLICY_ALLE;
    }

    /**
     * @param Site $site
     */
    public function setSiteSettings(Site $site)
    {

    }
}
