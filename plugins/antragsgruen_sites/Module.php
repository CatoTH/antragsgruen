<?php

namespace app\plugins\antragsgruen_sites;

use app\models\db\Consultation;
use app\models\layoutHooks\Hooks;
use app\models\settings\Layout;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     * @param string $domainPlain
     * @return array
     */
    public static function getManagerUrlRoutes($domainPlain)
    {
        $domPlainPaths = 'help|password|createsite';
        return [
            $domainPlain                                    => 'antragsgruen_sites/manager/index',
            $domainPlain . '/<_a:(' . $domPlainPaths . ')>' => 'antragsgruen_sites/manager/<_a>',
            $domainPlain . '/loginsaml'                     => 'user/loginsaml',
        ];
    }

    /**
     * @return string
     */
    public static function getDefaultRouteOverride()
    {
        return '/antragsgruen_sites/manager/index';
    }

    /**
     * @param \yii\web\Controller $controller
     * @return \yii\web\AssetBundle[]
     */
    public static function getActiveAssetBundles($controller)
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
