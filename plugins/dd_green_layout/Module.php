<?php

namespace app\plugins\dd_green_layout;

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
                'title'       => 'Discuss.green',
                'preview'     => $thumbBase . '/layout-preview-green.png',
                'bundle'      => Assets::class,
                'hooks'       => LayoutHooks::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
        ];
    }
}
