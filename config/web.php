<?php

$configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
require_once($configDir . 'JsonConfigTrait.php');
require_once($configDir . 'AntragsgruenApp.php');

if (YII_ENV == 'test') {
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_starts_with($requestUri ?? '', '/std/yfj-test')) {
        $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config_tests_yfj.json';
    } elseif (str_starts_with($requestUri ?? '', '/std/hv') || str_contains($requestUri, '%2Fstd%2Fhv&subdomain=std')) {
        $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config_tests_dbwv.json';
    } elseif (str_starts_with($requestUri ?? '', '/std/lv-sued') || str_contains($requestUri, '%2Fstd%2Flv-sued&subdomain=std')) {
        $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config_tests_dbwv.json';
    } else {
        $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config_tests.json';
    }
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
} catch (Exception $e) {
    die('Could not load configuration; probably due to a syntax error in config/config.json?');
}

if (YII_DEBUG === false) {
    $params->dbConnection['enableSchemaCache']   = true;
    $params->dbConnection['schemaCacheDuration'] = 3600;
    $params->dbConnection['schemaCache']         = 'cache';
}

$common = require(__DIR__ . DIRECTORY_SEPARATOR . 'common.php');

$cookieSettings = [
    'httpOnly' => true,
    'sameSite' => \yii\web\Cookie::SAME_SITE_LAX,
];
foreach ($params->getPluginClasses() as $plugin) {
    $cookieSettings = array_merge($cookieSettings, $plugin::getSessionCookieSettings());
}

if ((isset($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
    $cookieSettings['secure'] = true;
}

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
                'identityCookie' => array_merge($cookieSettings, ['name' => '_identity']),
            ],
            'authClientCollection' => [
                'class'   => 'yii\authclient\Collection',
                'clients' => $params->authClientCollection,
            ],
            'request'              => [
                'cookieValidationKey' => $params->randomSeed,
                'csrfCookie'          => $cookieSettings,

                // Trust proxies from reverse proxies on local networks - necessary for getIsSecureConnection()
                'trustedHosts' => [
                    '10.0.0.0/8',
                    '172.16.0.0/12',
                    '192.168.0.0/16',
                ],

                'parsers' => [
                    'application/json' => 'yii\web\JsonParser',
                ]
            ],
            'assetManager'         => [
                'appendTimestamp' => false,
            ],
        ],
    ]
);
if ($params->cookieDomain) {
    $config['components']['session'] = [
        'cookieParams' => array_merge($cookieSettings, ['domain' => $params->cookieDomain]),
    ];
} elseif ($params->domainPlain) {
    $config['components']['session'] = [
        'cookieParams' => array_merge($cookieSettings, ['domain' => '.' . parse_url($params->domainPlain, PHP_URL_HOST)]),
    ];
}

if (YII_ENV_DEV && file_exists($configFile) && !str_contains($_SERVER['HTTP_USER_AGENT'], 'pa11y')  ) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][]      = 'debug';
    $config['modules']['debug'] = [
        'class'      => 'yii\debug\Module',
        'allowedIPs' => ['*'],
    ];
}

return $config;
