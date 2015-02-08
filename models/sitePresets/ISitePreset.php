<?php


namespace app\models\sitePresets;


use app\models\db\Consultation;
use app\models\db\Site;

interface ISitePreset
{

    /**
     * @return string
     */
    public static function getTitle();

    /**
     * @return string
     */
    public static function getDescription();

    /**
     * @param Consultation $consultation
     */
    public function setConsultationSettings(Consultation $consultation);

    /**
     * @param Site $site
     */
    public function setSiteSettings(Site $site);
}
