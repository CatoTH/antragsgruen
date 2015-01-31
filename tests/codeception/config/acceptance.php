<?php
/**
 * Application configuration for acceptance tests
 */
$base_dir    = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$base_config = $base_dir . 'config' . DIRECTORY_SEPARATOR;

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../../config/web.php'),
    require(__DIR__ . '/config.php'),
    [

    ]
);
$config['components']['urlManager']['rules'] = require($base_config . "urls.php");

return $config;
