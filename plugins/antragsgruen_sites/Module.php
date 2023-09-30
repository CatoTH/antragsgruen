<?php

namespace app\plugins\antragsgruen_sites;

use app\models\db\Consultation;
use app\models\settings\Layout;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        $domPlainPaths = 'help|password|createsite|check-subdomain|legal|privacy|allsites';
        return [
            $domainPlain                                    => 'antragsgruen_sites/manager/index',
            $domainPlain . '/<_a:(' . $domPlainPaths . ')>' => 'antragsgruen_sites/manager/<_a>',
        ];
    }

    public static function getDefaultRouteOverride(): string
    {
        return '/antragsgruen_sites/manager/index';
    }

    /**
     * @return \yii\web\AssetBundle[]|string[]
     */
    public static function getActiveAssetBundles(\yii\web\Controller $controller): array
    {
        if (str_starts_with($controller->route, 'antragsgruen_sites')) {
            return [
                Assets::class,
            ];
        } else {
            return [];
        }
    }

    public static function getForcedLayoutHooks(Layout $layoutSettings, ?Consultation $consultation): array
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }
}
