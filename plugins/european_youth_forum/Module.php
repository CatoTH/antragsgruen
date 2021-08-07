<?php

namespace app\plugins\european_youth_forum;

use app\models\UserOrganization;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getUserOrganizations(): array
    {
        return [
            new UserOrganization("nyo", 'NYO'),
            new UserOrganization("ingyo", 'INGYO'),
        ];
    }
}
