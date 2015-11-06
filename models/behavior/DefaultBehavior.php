<?php
namespace app\models\behavior;

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
}
