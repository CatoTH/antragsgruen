<?php

namespace app\plugins\antragsgruen_sites;

use app\models\db\Consultation;
use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        $domPlainPaths = 'help|password|createsite|check-subdomain|legal|privacy';
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
     * @param \yii\web\Controller $controller
     * @return \yii\web\AssetBundle[]
     */
    public static function getActiveAssetBundles(\Yii\web\Controller $controller)
    {
        if (strpos($controller->route, 'antragsgruen_sites') === 0) {
            return [
                Assets::class,
            ];
        } else {
            return [];
        }
    }

    /**
     * @param Layout $layoutSettings
     * @param Consultation $consultation
     * @return Hooks[]
     */
    public static function getForcedLayoutHooks($layoutSettings, $consultation)
    {
        return [
            new LayoutHooks($layoutSettings, $consultation)
        ];
    }
}
