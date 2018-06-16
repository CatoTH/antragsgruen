<?php

namespace app\plugins\gruen_ci;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/gruen_ci/assets/';

    public $css = [
        'layout-gruen_ci.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
}