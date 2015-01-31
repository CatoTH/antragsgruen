<?php

Yii::setAlias('@tests', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests');

$common = require(__DIR__ . DIRECTORY_SEPARATOR . 'common.php');

return yii\helpers\ArrayHelper::merge($common, [
        'id'                  => 'basic-console',
        'controllerNamespace' => 'app\commands',
]);
