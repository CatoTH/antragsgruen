<?php

namespace app\plugins\european_youth_forum;

use app\models\policies\IPolicy;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    /**
     * @return string[]|IPolicy[]
     */
    public static function getCustomPolicies()
    {
        return [
            SelectedUsersPolicy::class,
        ];
    }
}
