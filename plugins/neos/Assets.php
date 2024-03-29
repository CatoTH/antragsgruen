<?php

namespace app\plugins\neos;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/neos/assets/';

    public $css = [
        'layout-neos.css',
    ];
    public $js = [
        'neos.js',
    ];
    public $depends = [
    ];

    public static ?string $myBaseUrl = null;

    /**
     * @param \yii\web\View $view
     */
    public static function register($view): Assets
    {
        $myself = parent::register($view);
        static::$myBaseUrl = $myself->baseUrl;
        return $myself;
    }
}
