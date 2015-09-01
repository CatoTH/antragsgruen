<?php

use app\models\settings\AntragsgruenApp;

/**
 * @var AntragsgruenApp $params
 */


require_once(__DIR__ . DIRECTORY_SEPARATOR . 'defines.php');

if (ini_get('date.timezone') == '') {
    date_default_timezone_set('Europe/Berlin');
}

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . 'INSTALLING')) {
    $domp         = trim($params->domainPlain, '/');
    $urls         = [
        $domp . '/<_a:(antragsgrueninit|antragsgrueninitdbtest)>' => 'manager/<_a>',
    ];
    $defaultRoute = 'manager/antragsgrueninit';
} else {
    $urls         = require(__DIR__ . DIRECTORY_SEPARATOR . 'urls.php');
    $defaultRoute = ($params->multisiteMode ? 'manager/index' : 'consultation/index');
}

return [
    'bootstrap'    => ['log'],
    'basePath'     => dirname(__DIR__),
    'components'   => [
        'cache'      => [
            'class' => 'yii\caching\FileCache',
        ],
        'mailer'     => [
            'class' => 'yii\swiftmailer\Mailer',
        ],
        'log'        => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets'    => [
                [
                    'class'  => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db'         => $params->dbConnection,
        'urlManager' => [
            'class'           => 'app\components\UrlManager',
            'showScriptName'  => false,
            'enablePrettyUrl' => $params->prettyUrl,
            'rules'           => $urls
        ],
        'i18n'       => [
            'translations' => [
                '*' => [
                    'class'    => 'app\components\MessageSource',
                    'basePath' => '@app/messages',
                ],
            ],

        ],
    ],
    'defaultRoute' => $defaultRoute,
    'params'       => $params,
    'language'     => $params->baseLanguage,
];
