<?php

namespace app\plugins\swagger_ui;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/swagger_ui/assets/';

    public $css = [
        'swagger-ui.css',
    ];
    public $js = [
        'swagger-ui-bundle.js',
        './swagger-ui-standalone-preset.js',
    ];
    public $depends = [
    ];
}
