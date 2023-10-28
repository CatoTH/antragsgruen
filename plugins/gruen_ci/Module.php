<?php

declare(strict_types=1);

namespace app\plugins\gruen_ci;

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
                'title'       => 'Grünes CI',
                'preview'     => $thumbBase . '/layout-preview-green.png',
                'bundle'      => Assets2::class,
                'hooks'       => LayoutHooks2::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
            'old' => [
                'title'       => 'Grünes CI (alt)',
                'preview'     => $thumbBase . '/layout-preview-old.png',
                'bundle'      => Assets1::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
            'layout2023' => [
                'title'       => 'Grünes CI 2023',
                'preview'     => $thumbBase . '/layout-preview-ci3.png',
                'bundle'      => Assets3::class,
                'hooks'       => LayoutHooks3::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
        ];
    }
}
