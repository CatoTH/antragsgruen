<?php
/**
 * Application configuration shared by all test types
 */
use app\models\settings\AntragsgruenApp;

$base_dir    = __DIR__ . DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$base_config = $base_dir . 'config' . DIRECTORY_SEPARATOR;

require_once($base_dir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'AntragsgruenApp.php');

$params    = require($base_config . 'config_tests.php');
/** @var AntragsgruenApp $params */

return [
    'components' => [
        'db'         => $params->dbConnection,
        'mailer'     => [
            'useFileTransport' => true,
        ],
        'urlManager' => [
            'showScriptName' => true
        ],
    ],
    'params'     => $params
];
