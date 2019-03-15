<?php

namespace app\plugins\member_petitions;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/member_petitions/assets/';

    public $css = [
        'memberpetitions.css',
    ];
    public $js = [
        'memberpetitions.js',
    ];
    public $depends = [
    ];
}
