<?php

namespace app\plugins\member_petitions;

use app\models\policies\IPolicy;
use app\models\siteSpecificBehavior\DefaultBehavior;

class SiteSpecificBehavior extends DefaultBehavior
{
    /**
     * @return string|Permissions
     */
    public static function getPermissionsClass()
    {
        return Permissions::class;
    }

    /**
     * @return string[]|IPolicy[]
     */
    public static function getCustomPolicies()
    {
        return [
            MotionPolicy::class,
        ];
    }

    public static function hasSiteHomePage(): bool
    {
        return true;
    }

    public static function getSiteHomePage(): string
    {
        $controller = \Yii::$app->controller;
        return $controller->render('@app/plugins/member_petitions/views/index');
    }
}
