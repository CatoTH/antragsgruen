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
                'title'  => 'Grünes CI v2',
                'bundle' => Assets::class,
                'hooks'  => LayoutHooks::class,
            ]
        ];
    }
}
