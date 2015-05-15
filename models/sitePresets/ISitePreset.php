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
     * @return array
     */
    public static function getDetailDefaults();

    /**
     * @param Consultation $consultation
     */
    public function setConsultationSettings(Consultation $consultation);

    /**
     * @param Site $site
     */
    public function setSiteSettings(Site $site);

    /**
     * @param Consultation $consultation
     */
    public function createMotionSections(Consultation $consultation);

    /**
     * @param Consultation $consultation
     */
    public function createMotionTypes(Consultation $consultation);

    /**
     * @param Consultation $consultation
     */
    public function createAgenda(Consultation $consultation);
}
