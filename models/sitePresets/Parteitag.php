<?php

namespace app\models\sitePresets;


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
     * @param $siteCreateData
     * @return \app\models\db\Site
     */
    public function createSiteAndConsultation($siteCreateData)
    {
        $site = new \app\models\db\Site();
        // @todo Subdomain etc.
        $site->save();

        $consultation         = new \app\models\db\Consultation();
        $consultation->siteId = $site->id;
        // @todo
        $consultation->save();

        $site->currentConsultationId = $consultation->id;
        $site->save();


        $consultation->refresh();
        $site->refresh();

        return $consultation;
    }
}
