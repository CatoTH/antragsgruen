<?php
/**
 * Application configuration shared by all test types
 */
$base_dir    = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' .
    DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$base_config = $base_dir . 'config' . DIRECTORY_SEPARATOR;

require_once($base_dir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'AntragsgruenApp.php');
require_once(__DIR__ . DIRECTORY_SEPARATOR . 'AntragsgruenSetupDB.php');

$params    = require($base_config . 'local' . DIRECTORY_SEPARATOR . 'params_tests.php');
$db_params = require($base_config . 'local' . DIRECTORY_SEPARATOR . 'db_tests.php');


return [
    'components' => [
        'db'         => $db_params,
        'mailer'     => [
            'useFileTransport' => true,
        ],
        'urlManager' => [
            'showScriptName' => true
        ],
    ],
    'params'     => $params
];
