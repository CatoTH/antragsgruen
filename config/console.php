<?php

require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' .
    DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'AntragsgruenApp.php');

$params = require(__DIR__ . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'params.php');
$db_params = require(__DIR__ . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'db.php');

$common = require(__DIR__ . DIRECTORY_SEPARATOR . 'common.php');

return yii\helpers\ArrayHelper::merge(
    $common,
    [
        'id'                  => 'basic-console',
        'controllerNamespace' => 'app\commands',
    ]
);
