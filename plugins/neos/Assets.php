<?php

namespace app\plugins\neos;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/neos/assets/';

    public $css = [
        'layout-neos.css',
    ];
    public $js = [
        'neos.js',
    ];
    public $depends = [
    ];
}