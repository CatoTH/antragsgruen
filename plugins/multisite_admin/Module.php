<?php

namespace app\plugins\multisite_admin;

use app\models\db\Consultation;
use app\models\settings\Layout;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        $domPlainPaths = 'password|createsite|check-subdomain';
        return [
            $domainPlain                                     => 'multisite_admin/manager/index',
            $domainPlain . '/<_a:(' . $domPlainPaths . ')>'  => 'multisite_admin/manager/<_a>',
        ];
    }

    public static function getDefaultRouteOverride(): string
    {
        return '/multisite_admin/manager/index';
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }
}
