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

    /** @var null|string */
    public static $myBaseUrl = null;

    /**
     * @param \yii\web\View $view
     * @return AssetBundle
     */
    public static function register($view)
    {
        $myself = parent::register($view);
        static::$myBaseUrl = $myself->baseUrl;
        return $myself;
    }
}
