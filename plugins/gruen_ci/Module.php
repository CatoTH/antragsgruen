<?php

namespace app\plugins\gruen_ci;

use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /**
     * @return array
     */
    public static function getProvidedLayouts()
    {
        return [
            'std' => [
                'title'       => 'Grünes CI',
                'bundle'      => Assets2::class,
                'hooks'       => LayoutHooks::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
            'old' => [
                'title'       => 'Grünes CI (alt)',
                'bundle'      => Assets1::class,
                'odtTemplate' => __DIR__ . '/OpenOffice-Template-Gruen.odt',
            ],
        ];
    }
}
