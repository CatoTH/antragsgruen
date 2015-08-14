<?php

require_once(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' .
    DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'AntragsgruenApp.php');

/** @var \app\models\settings\AntragsgruenApp $params */
if (YII_ENV == 'test') {
    $params = require(__DIR__ . DIRECTORY_SEPARATOR . 'config_tests.php');
} else {
    $params = require(__DIR__ . DIRECTORY_SEPARATOR . 'config.php');
}

if (YII_DEBUG === false) {
    $params->dbConnection['enableSchemaCache']   = true;
    $params->dbConnection['schemaCacheDuration'] = 3600;
    $params->dbConnection['schemaCache']         = 'cache';
}

$common = require(__DIR__ . DIRECTORY_SEPARATOR . 'common.php');

$config = yii\helpers\ArrayHelper::merge(
    $common,
    [
        'id'         => 'basic',
        'components' => [
            'errorHandler'         => [
                //'errorAction' => 'manager/error',
            ],
            'user'                 => [
                'identityClass'   => 'app\models\db\User',
                'enableAutoLogin' => true,
            ],
            'authClientCollection' => [
                'class'   => 'yii\authclient\Collection',
                'clients' => $params->authClientCollection,
            ],
            'request'              => [
                'cookieValidationKey' => $params->cookieValidationKey,
            ],
        ],
    ]
);

if (YII_ENV_DEV && strpos($_SERVER['HTTP_USER_AGENT'], 'pa11y') === false) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][]      = 'debug';
    $config['modules']['debug'] = [
        'class'      => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
