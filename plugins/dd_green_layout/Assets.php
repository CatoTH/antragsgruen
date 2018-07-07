<?php

namespace app\plugins\dd_green_layout;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/dd_green_layout/assets/';

    public $css = [
        'layout-dd_green_layout.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
}