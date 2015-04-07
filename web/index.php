<?php
// @codingStandardsIgnoreFile

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');


$configfile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';
if (!file_exists($configfile)) {
    die('Antragsgrün is not configured yet. Please create a config/config.php by using the config.template.php.');
}

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();
