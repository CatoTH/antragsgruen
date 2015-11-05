<?php
namespace app\models\behavior;

class DefaultBehavior
{
    /**
     * @param array $sites
     * @return array
     */
    public static function getManagerSidebarSites($sites)
    {
        return $sites;
    }
}
