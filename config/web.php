<?php

$configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' .
    DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
require_once($configDir . 'JsonConfigTrait.php');
require_once($configDir . 'AntragsgruenApp.php');

if (YII_ENV == 'test') {
    $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config_tests.json';
} elseif (isset($_SERVER['ANTRAGSGRUEN_CONFIG'])) {
    $configFile = $_SERVER['ANTRAGSGRUEN_CONFIG'];
} else {
    $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';
}
if (file_exists($configFile)) {
    $config = file_get_contents($configFile);
} else {
    $config = '';
}
try {
    $params = new \app\models\settings\AntragsgruenApp($config);
} catch (\Exception $e) {
    die('Could not load configuration; probably due to a syntax error in config/config.json?');
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
                'cookieValidationKey' => $params->randomSeed,
            ],
        ],
    ]
);
if ($params->cookieDomain) {
    $config['components']['session'] = [
        'cookieParams' => [
            'httponly' => true,
            'domain'   => $params->cookieDomain,
        ]
    ];
} elseif ($params->domainPlain) {
    $config['components']['session'] = [
        'cookieParams' => [
            'httponly' => true,
            'domain'   => '.' . parse_url($params->domainPlain, PHP_URL_HOST),
        ]
    ];
}

if ($params->hasWurzelwerk && !isset($config['components']['authClientCollection']['clients']['wurzelwerk'])) {
    $config['components']['authClientCollection']['clients']['wurzelwerk'] = [
        'class' => 'app\components\WurzelwerkAuthClient',
    ];
}
if (YII_ENV_DEV && file_exists($configFile) && strpos($_SERVER['HTTP_USER_AGENT'], 'pa11y') === false) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][]      = 'debug';
    $config['modules']['debug'] = [
        'class'      => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
