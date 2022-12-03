<?php

namespace app\plugins\member_petitions;

use app\models\http\HtmlResponse;
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
    public static function getCustomPolicies(): array
    {
        return [
            MotionPolicy::class,
        ];
    }

    public static function hasSiteHomePage(): bool
    {
        return true;
    }

    public static function getSiteHomePage(): HtmlResponse
    {
        $controller = \Yii::$app->controller;
        return new HtmlResponse($controller->render('@app/plugins/member_petitions/views/index'));
    }
}
