<?php

use app\models\settings\AntragsgruenApp;

/**
 * @var AntragsgruenApp $params
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'defines.php');

if (ini_get('date.timezone') == '') {
    date_default_timezone_set('Europe/Berlin');
}
ini_set('tidy.clean_output', false);
ini_set('default_charset', 'UTF-8');

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'INSTALLING')) {
    $domp         = trim($params->domainPlain, '/');
    $urls         = [
        $domp . '/<_a:(index|db-test)>' => 'installation/<_a>',
    ];
    $defaultRoute = 'installation/index';
    define('INSTALLING_MODE', true);
} else {
    $urls         = require(__DIR__ . DIRECTORY_SEPARATOR . 'urls.php');
    $defaultRoute = ($params->multisiteMode ? 'manager/index' : 'consultation/index');
}

if (defined('INSTALLING_MODE') || YII_ENV == 'test') {
    $params->dbConnection['class'] = 'yii\db\Connection';
} else {
    $params->dbConnection['class'] = 'app\components\DBConnection';
}

$components = [
    'cache'        => [
        'class' => ($params->redis ? 'yii\redis\Cache' : 'yii\caching\FileCache'),
    ],
    'assetManager' => [
        'appendTimestamp' => true,
    ],
    'mailer'       => [
        'class' => 'yii\swiftmailer\Mailer',
    ],
    'log'          => [
        'traceLevel' => YII_DEBUG ? 3 : 0,
        'targets'    => [
            [
                'class'  => 'yii\log\FileTarget',
                'levels' => ['error', 'warning'],
                'except' => [
                    'yii\web\HttpException:404',
                ],
            ],
        ],
    ],
    'db'           => $params->dbConnection,
    'urlManager'   => [
        'class'           => 'app\components\UrlManager',
        'showScriptName'  => false,
        'enablePrettyUrl' => $params->prettyUrl,
        'rules'           => $urls
    ],
    'i18n'         => [
        'translations' => [
            '*' => [
                'class'    => 'app\components\MessageSource',
                'basePath' => '@app/messages',
            ],
        ],
    ],
];

if ($params->redis) {
    $components['redis']   = array_merge(['class' => 'yii\redis\Connection'], $params->redis);
    $components['session'] = ['class' => 'yii\redis\Session'];
}

return [
    'name'         => 'Antragsgrün',
    'bootstrap'    => ['log'],
    'basePath'     => dirname(__DIR__),
    'components'   => $components,
    'defaultRoute' => $defaultRoute,
    'params'       => $params,
    'language'     => $params->baseLanguage,
];
