<?php

$configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' .
    DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
require_once($configDir . 'JsonConfigTrait.php');
require_once($configDir . 'AntragsgruenApp.php');


if (isset($_SERVER['ANTRAGSGRUEN_CONFIG'])) {
    $configFile = $_SERVER['ANTRAGSGRUEN_CONFIG'];
} elseif (isset($_ENV['ANTRAGSGRUEN_CONFIG'])) {
    $configFile = $_SERVER['ANTRAGSGRUEN_CONFIG'];
} else {
    $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';
}

$config = file_get_contents($configFile);
$params = new \app\models\settings\AntragsgruenApp($config);
$common = require(__DIR__ . DIRECTORY_SEPARATOR . 'common.php');
unset($common['defaultRoute']);

return yii\helpers\ArrayHelper::merge(
    $common,
    [
        'id'                  => 'basic-console',
        'controllerNamespace' => 'app\commands',
    ]
);
