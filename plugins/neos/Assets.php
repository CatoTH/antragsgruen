<?php

namespace app\plugins\neos;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/neos/';

    public $css = [
        'layout-neos.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
    public $publishOptions = [
        'only' => [
            'montserrat/*',
            'layout-neos.css',
        ]
    ];
}