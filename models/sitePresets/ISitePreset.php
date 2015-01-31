<?php


namespace app\models\sitePresets;


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
     * @param $siteCreateData
     * @return \app\models\db\Consultation
     */
    public function createSiteAndConsultation($siteCreateData);
}
