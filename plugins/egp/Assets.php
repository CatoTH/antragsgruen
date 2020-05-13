<?php

namespace app\plugins\egp;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/egp/assets/';

    public $css = [
        'layout-egp.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
}
