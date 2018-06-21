<?php

namespace app\plugins\gruen_ci;

use app\plugins\ModuleBase;
use yii\web\View;

class Module extends ModuleBase
{
    /**
     * @param View|null $view
     * @return array
     */
    public static function getProvidedLayouts($view = null)
    {
        if ($view) {
            $asset = ThumbnailAssets::register($view);
            $thumbBase = $asset->baseUrl;
        } else {
            $thumbBase = '/';
        }

        return [
            'std' => [
                'title'       => 'Grünes CI',
                'preview'     => $thumbBase . '/layout-preview-green.png',
                'bundle'      => Assets2::class,
                'hooks'       => LayoutHooks::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
            'old' => [
                'title'       => 'Grünes CI (alt)',
                'preview'     => $thumbBase . '/layout-preview-old.png',
                'bundle'      => Assets1::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
        ];
    }
}
