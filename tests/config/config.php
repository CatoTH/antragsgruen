<?php
/**
 * Application configuration shared by all test types
 */

$baseDir    = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
$baseConfig = $baseDir . 'config' . DIRECTORY_SEPARATOR;

require_once($baseDir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'JsonConfigTrait.php');
require_once($baseDir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'AntragsgruenApp.php');

if (strpos($_SERVER['REQUEST_URI'] ?? '', '/std/yfj-test') === 0) {
    $config = file_get_contents($baseConfig . DIRECTORY_SEPARATOR . 'config_tests_yfj.json');
} else {
    $config = file_get_contents($baseConfig . DIRECTORY_SEPARATOR . 'config_tests.json');
}
$params = new \app\models\settings\AntragsgruenApp($config);

return [
    'components' => [
        'db'         => $params->dbConnection,
        'mailer'     => [
            'useFileTransport' => true,
        ],
    ],
    'params'     => $params
];
