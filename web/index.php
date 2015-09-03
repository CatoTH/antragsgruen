<?php
// @codingStandardsIgnoreFile

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');


$configfile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.json';
if (!file_exists($configfile)) {
    die('Antragsgrün is not configured yet. Please create a config/config.php by using the config.template.php.');
}

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

try {
    $config = require(__DIR__ . '/../config/web.php');

    (new yii\web\Application($config))->run();
} catch (\yii\base\InvalidConfigException $e) {
    echo 'Leider ist die Antragsgrün-Konfigurationsdatei (config/config.json) fehlerhaft.
    Du kannst auf folgende Weisen versuchen, sie zu korrigieren:<ul>
    <li>Die Datei von Hand bearbeiten und den Fehler ausfindig machen und korrigieren.</li>
    <li>Den Installationsmodus aktivieren (die Datei config/INSTALLING anlegen) und eine beliebige Seite aufrufen, um in den Installationsmodus zu gelangen.</li>
    </ul>';
}