<?php
namespace app\models\behavior;

use app\models\db\Consultation;

class DefaultBehavior
{
    /**
     * @param array $sites
     * @return array
     */
    public static function getManagerCurrentSidebarSites($sites)
    {
        return $sites;
    }

    /**
     * @param array $sites
     * @return array
     */
    public static function getManagerOldSidebarSites($sites)
    {
        return $sites;
    }

    /**
     * Is shown at the top of the manager page, mainly to promote current consultations
     *
     * @return string
     */
    public static function getManagerCurrentHint()
    {
        return '';
    }
}
