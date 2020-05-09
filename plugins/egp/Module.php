<?php

namespace app\plugins\egp;

use app\models\db\Site;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     * @param Site $site
     *
     * @return SiteSpecificBehavior|string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getSiteSpecificBehavior(Site $site)
    {
        return SiteSpecificBehavior::class;
    }
}
