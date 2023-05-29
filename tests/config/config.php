<?php
/**
 * Application configuration shared by all test types
 */

use app\models\settings\AntragsgruenApp;

$baseDir    = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
$baseConfig = $baseDir . 'config' . DIRECTORY_SEPARATOR;

require_once($baseDir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'JsonConfigTrait.php');
require_once($baseDir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'AntragsgruenApp.php');

if (str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/std/yfj-test')) {
    $config = file_get_contents($baseConfig . DIRECTORY_SEPARATOR . 'config_tests_yfj.json');
} else {
    $config = file_get_contents($baseConfig . DIRECTORY_SEPARATOR . 'config_tests.json');
}
$params = new AntragsgruenApp($config);

return [
    'components' => [
        'db'         => $params->dbConnection,
        'mailer'     => [
            'useFileTransport' => true,
        ],
    ],
    'params'     => $params
];
