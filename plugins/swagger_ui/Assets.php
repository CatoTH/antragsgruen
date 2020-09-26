<?php

namespace app\plugins\swagger_ui;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/swagger_ui/assets/';

    public $css = [];
    public $js = [];
    public $depends = [];
}
