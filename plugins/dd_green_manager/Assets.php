<?php

namespace app\plugins\dd_green_manager;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/dd_green_manager/assets/';

    public $css = [
        'sites.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
}
