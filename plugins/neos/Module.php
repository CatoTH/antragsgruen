<?php

namespace app\plugins\neos;

use app\plugins\ModuleBase;
use yii\web\AssetBundle;

class Module extends ModuleBase
{
    /**
     */
    public function init()
    {
        parent::init();
    }

    /**
     * @return array
     */
    public static function getProvidedLayouts()
    {
        return [
            'std' => [
                'title'  => 'NEOS',
                'bundle' => Assets::class,
            ]
        ];
    }
}
