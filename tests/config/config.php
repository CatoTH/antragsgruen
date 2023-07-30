<?php
/**
 * Application configuration shared by all test types
 */

use app\models\settings\AntragsgruenApp;

$baseDir    = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR;
$baseConfig = $baseDir . 'config' . DIRECTORY_SEPARATOR;

require_once($baseDir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'JsonConfigTrait.php');
require_once($baseDir . 'models' . DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR . 'AntragsgruenApp.php');

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (str_starts_with($requestUri ?? '', '/std/yfj-test')) {
    $config = file_get_contents($baseConfig . DIRECTORY_SEPARATOR . 'config_tests_yfj.json');
} elseif (str_starts_with($requestUri ?? '', '/std/hv') || str_contains($requestUri, '%2Fstd%2Flv-sued&subdomain=std')) {
    $config = file_get_contents($baseConfig . DIRECTORY_SEPARATOR . 'config_tests_dbwv.json');
} elseif (str_starts_with($requestUri ?? '', '/std/lv-sued') || str_contains($requestUri, '%2Fstd%2Flv-sued&subdomain=std')) {
    $config = file_get_contents($baseConfig . DIRECTORY_SEPARATOR . 'config_tests_dbwv.json');
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
