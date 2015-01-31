<?php

$common = require(__DIR__ . DIRECTORY_SEPARATOR . 'common.php');

$config = yii\helpers\ArrayHelper::merge($common, [
    'id'         => 'basic',
    'components' => [
        'errorHandler' => [
            'errorAction' => 'info/error',
        ],
        'user'         => [
            'identityClass'   => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'request'      => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'eMsWkKlOwN7qLaGI8R04JsN4eQInTqx3',
        ],
    ],
]);

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][]      = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';
}

return $config;
