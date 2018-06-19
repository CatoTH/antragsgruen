<?php

namespace app\plugins\antragsgruen_sites;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/antragsgruen_sites/assets/';

    public $css = [
        'sites.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
}
