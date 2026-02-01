<?php

$configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'models' .
    DIRECTORY_SEPARATOR . 'settings' . DIRECTORY_SEPARATOR;
require_once($configDir . 'JsonConfigTrait.php');
require_once($configDir . 'AntragsgruenApp.php');


if (isset($_SERVER['ANTRAGSGRUEN_CONFIG'])) {
    $configFile = $_SERVER['ANTRAGSGRUEN_CONFIG'];
} elseif (isset($_ENV['ANTRAGSGRUEN_CONFIG'])) {
    $configFile = $_ENV['ANTRAGSGRUEN_CONFIG'];
} else {
    $configFile = __DIR__ . DIRECTORY_SEPARATOR . 'config.json';
}

// Load config.json if it exists, otherwise use empty JSON (will load from environment)
if (file_exists($configFile)) {
    $config = file_get_contents($configFile);
} else {
    // No config.json found - will load from environment variables
    $config = '{}';
}

try {
    $params = new \app\models\settings\AntragsgruenApp($config);
} catch (Exception $e) {
    fwrite(STDERR, "Configuration error: " . $e->getMessage() . "\n");
    if (!file_exists($configFile)) {
        fwrite(STDERR, "No config.json file found. Set configuration via environment variables.\n");
        fwrite(STDERR, "See docs/environment-variables.md for details.\n");
    }
    exit(1);
}
$common = require(__DIR__ . DIRECTORY_SEPARATOR . 'common.php');
unset($common['defaultRoute']);

return yii\helpers\ArrayHelper::merge(
    $common,
    [
        'id'                  => 'basic-console',
        'controllerNamespace' => 'app\commands',
    ]
);
