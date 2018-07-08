<?php

namespace app\plugins\green_layout;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/green_layout/assets/';

    public $css = [
        'layout-green_layout.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
}