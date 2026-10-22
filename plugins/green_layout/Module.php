<?php

namespace app\plugins\green_layout;

use app\models\layoutHooks\Hooks;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     * @return array<string, array{title: string, preview: string|null, bundle: class-string<\app\components\view\AssetBundle>, hooks?: class-string<Hooks>, odtTemplate?: string}>
     */
    public static function getProvidedLayouts(): array
    {
        $thumbBase = (new ThumbnailAssets())->getBaseUrl();

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
