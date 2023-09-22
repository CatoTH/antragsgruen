<?php

namespace app\plugins\green_layout;

use app\models\layoutHooks\Hooks;
use app\plugins\ModuleBase;
use yii\web\View;

class Module extends ModuleBase
{
    /**
     * @return array<string, array{title: string, preview: string|null, bundle: class-string, hooks?: class-string<Hooks>, odtTemplate?: string}>
     */
    public static function getProvidedLayouts(?View $view = null): array
    {
        if ($view) {
            $asset = ThumbnailAssets::register($view);
            $thumbBase = $asset->baseUrl;
        } else {
            $thumbBase = '/';
        }

        return [
            'std' => [
                'title'       => 'Discuss.green',
                'preview'     => $thumbBase . '/layout-preview-green.png',
                'bundle'      => Assets::class,
                'hooks'       => LayoutHooks::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
        ];
    }
}
