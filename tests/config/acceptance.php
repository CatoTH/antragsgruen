<?php
/**
 * Application configuration for acceptance tests
 */
$baseDir    = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$baseConfig = $baseDir . 'config' . DIRECTORY_SEPARATOR;

$config = yii\helpers\ArrayHelper::merge(
    require(__DIR__ . '/../../config/web.php'),
    require(__DIR__ . '/config.php'),
    []
);
$config['components']['urlManager']['rules'] = require($baseConfig . "urls.php");

return $config;
