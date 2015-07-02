<?php

/*
if (!class_exists("Yii")) {

    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'test');

    require(__DIR__ . '/../../vendor/autoload.php');
    require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

}
*/

use Codeception\Util\Autoload;

Autoload::addNamespace('app', __DIR__ . DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
