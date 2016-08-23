<?php
// @codingStandardsIgnoreFile

if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'DEBUG')) {
    defined('YII_DEBUG') or define('YII_DEBUG', true);
    defined('YII_ENV') or define('YII_ENV', 'dev');
} else {
    defined('YII_DEBUG') or define('YII_DEBUG', false);
    defined('YII_ENV') or define('YII_ENV', 'production');
}

$configDir   = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config';
$configFile  = $configDir . DIRECTORY_SEPARATOR . 'config.json';
$installFile = $configDir . DIRECTORY_SEPARATOR . 'INSTALLING';
if (!file_exists($configFile) && !file_exists($installFile)) {
    die('Antragsgrün is not configured yet. Please create the config/INSTALLING file and call this site again to open the installation wizard.');
}

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../components/yii/Application.php');

try {
    $config = require(__DIR__ . '/../config/web.php');

    (new \app\components\yii\Application($config))->run();
} catch (\yii\base\InvalidConfigException $e) {
    $error = htmlentities($e->getMessage(), ENT_COMPAT, 'UTF-8');
    echo str_replace('%ERROR%', $error, 'Leider ist die Antragsgrün-Konfigurationsdatei (config/config.json) fehlerhaft.
    Die Fehlermeldung lautet: %ERROR%<br><br>
    Du kannst auf folgende Weisen versuchen, sie zu korrigieren:<ul>
    <li>Die Datei von Hand bearbeiten und den Fehler ausfindig machen und korrigieren.</li>
    <li>Den Installationsmodus aktivieren (die Datei config/INSTALLING anlegen) und eine beliebige Seite aufrufen, um in den Installationsmodus zu gelangen.</li>
    </ul>');
}
