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

// Pin PHP and MySQL to UTC, so that the TIMESTAMP values of the test fixtures and the dates rendered
// from them do not depend on the time zone of the machine running the tests.
// Both the web server and the test runner (which loads the fixtures) use this configuration.
$dbConnection = $params->dbConnection;
$dbConnection['on afterOpen'] = function (\yii\base\Event $event): void {
    /** @var \yii\db\Connection $connection */
    $connection = $event->sender;
    $connection->pdo->exec("SET time_zone = '+00:00'");
};

return [
    'timeZone'   => 'UTC',
    'components' => [
        'db'         => $dbConnection,
        'mailer'     => [
            'useFileTransport' => true,
        ],
    ],
    'params'     => $params
];
