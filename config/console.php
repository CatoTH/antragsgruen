<?php

$configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' .
    DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
require_once($configDir . 'JsonConfigTrait.php');
require_once($configDir . 'AntragsgruenApp.php');

$config = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'config.json');
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
