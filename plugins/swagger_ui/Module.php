<?php

declare(strict_types=1);

namespace app\plugins\swagger_ui;

use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     * @return \yii\web\AssetBundle[]|string[]
     */
    public static function getActiveAssetBundles(\yii\web\Controller $controller): array
    {
        return [
            Assets::class,
        ];
    }

    public static function getAllUrlRoutes(string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return [
            $dom . 'api-docs/openapi.yml'  => 'swagger_ui/swaggerui/openapi',
            $dom . 'api-docs/index.html'   => 'swagger_ui/swaggerui/index',
            $dom . 'api-docs/<action:\w*>' => 'swagger_ui/swaggerui/index',
        ];
    }
}
