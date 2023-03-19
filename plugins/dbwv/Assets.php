<?php

declare(strict_types=1);

namespace app\plugins\dbwv;

use yii\web\AssetBundle;

class Assets extends AssetBundle
{
    public $sourcePath = '@app/plugins/dbwv/assets/';

    public $css = [
        'layout-dbwv.css',
    ];
    public $js = [
        //'dbwv.js',
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
