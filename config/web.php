<?php

$configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' .
    DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
require_once($configDir . 'JsonConfigTrait.php');
require_once($configDir . 'AntragsgruenApp.php');

if (YII_ENV == 'test') {
    $config = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'config_tests.json');
} else {
    $config = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'config.json');
}
$params = new \app\models\settings\AntragsgruenApp($config);

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
                'cookieValidationKey' => $params->randomSeed,
            ],
        ],
    ]
);
if ($params->hasWurzelwerk && !isset($config['components']['authClientCollection']['clients']['wurzelwerk'])) {
    $config['components']['authClientCollection']['clients']['wurzelwerk'] = [
        'class' => 'app\components\WurzelwerkAuthClient',
    ];
}
if (YII_ENV_DEV && strpos($_SERVER['HTTP_USER_AGENT'], 'pa11y') === false) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][]      = 'debug';
    $config['modules']['debug'] = [
        'class'      => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
