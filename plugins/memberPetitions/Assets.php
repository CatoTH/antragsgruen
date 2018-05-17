<?php

namespace app\plugins\memberPetitions;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/memberPetitions/assets/';

    public $css = [
        'memberpetitions.css',
    ];
    public $js = [
    ];
    public $depends = [
    ];
}
