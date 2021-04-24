<?php

namespace app\plugins\antragsgruen_sites;

use app\models\db\Consultation;
use app\models\settings\Layout;
use app\plugins\ModuleBase;
use yii\helpers\Url;

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
        if (strpos($controller->route, 'antragsgruen_sites') === 0) {
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

    public static function getGeneratedRoute(array $routeParts, string $originallyGeneratedRoute): ?string
    {
        if ($routeParts[0] === '/motion/pdf') {
            $routeParts[0] = '/motion/pdfamendcollection';
            return Url::toRoute($routeParts);
        }
        return null;
    }
}
