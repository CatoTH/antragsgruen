<?php
// @codingStandardsIgnoreFile

if (defined('YII_ENV')) {
    die('Recursive call?');
}

if (in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) && file_exists(__DIR__ . '/../config/TEST_DOMAIN')) {
    if ($_SERVER['HTTP_HOST'] === trim(file_get_contents(__DIR__ . '/../config/TEST_DOMAIN'))) {
        defined('YII_DEBUG') or define('YII_DEBUG', false);
        defined('YII_ENV') or define('YII_ENV', 'test');
    }
}

if (!defined('YII_ENV')) {
    if (file_exists(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'DEBUG')) {
        define('YII_DEBUG', true);
        define('YII_ENV', 'dev');
    } else {
        define('YII_DEBUG', false);
        define('YII_ENV', 'production');
    }

    $configDir   = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config';
    $configFile  = $configDir . DIRECTORY_SEPARATOR . 'config.json';
    $installFile = $configDir . DIRECTORY_SEPARATOR . 'INSTALLING';
    if (!file_exists($configFile) && !file_exists($installFile)) {
        die('Antragsgrün is not configured yet. Please create the config/INSTALLING file and call this site again to open the installation wizard.');
    }
}

$autoloader = require(__DIR__ . '/../vendor/autoload.php');
$autoloader->add('setasign\FpdiPdfParser', __DIR__ . '/../components/fpdi/src/');

require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
require(__DIR__ . '/../components/yii/Application.php');

try {
    if (YII_ENV === 'test') {
        $config = require(__DIR__ . '/../tests/config/acceptance.php');
    } else {
        $config = require(__DIR__ . '/../config/web.php');
    }

    (new \app\components\yii\Application($config))->run();
} catch (\yii\base\InvalidConfigException $e) {
    $error = htmlentities($e->getMessage(), ENT_COMPAT, 'UTF-8');
    $file  = (isset($_SERVER['ANTRAGSGRUEN_CONFIG']) ? $_SERVER['ANTRAGSGRUEN_CONFIG'] : 'config/config.json');
    echo str_replace('%ERROR%', $error, 'Leider ist die Antragsgrün-Konfigurationsdatei (' . $file . ') fehlerhaft.
    Die Fehlermeldung lautet: %ERROR%<br><br>
    Du kannst auf folgende Weisen versuchen, sie zu korrigieren:<ul>
    <li>Die Datei von Hand bearbeiten und den Fehler ausfindig machen und korrigieren.</li>
    <li>Den Installationsmodus aktivieren (die Datei config/INSTALLING anlegen) und eine beliebige Seite aufrufen, um in den Installationsmodus zu gelangen.</li>
    </ul>');
}
