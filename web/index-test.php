<?php
// @codingStandardsIgnoreFile

// NOTE: Make sure this file is not accessible when deployed to production
if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1'])) {
    die('You are not allowed to access this file.');
}

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

try {
    $config = require(__DIR__ . '/../tests/config/acceptance.php');

    (new yii\web\Application($config))->run();
} catch (\yii\base\InvalidConfigException $e) {
    echo 'Leider ist die Antragsgrün-Konfigurationsdatei (config/config.json) fehlerhaft.
    Du kannst auf folgende Weisen versuchen, sie zu korrigieren:<ul>
    <li>Die Datei von Hand bearbeiten und den Fehler ausfindig machen und korrigieren.</li>
    <li>Den Installationsmodus aktivieren (die Datei config/INSTALLING anlegen) und eine beliebige Seite aufrufen, um in den Installationsmodus zu gelangen.</li>
    </ul>';
}