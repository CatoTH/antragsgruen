<?php
// @codingStandardsIgnoreFile

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'production');


$configDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config';
$configFile = $configDir . DIRECTORY_SEPARATOR . 'config.json';
$installFile = $configDir . DIRECTORY_SEPARATOR . 'INSTALLING';
if (!file_exists($configFile) && !file_exists($installFile) && !isset($_ENV['APP_DOMAIN'])) {
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && str_starts_with($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'de')) {
        die('Antragsgrün ist noch nicht eingerichtet. Bitte lege die Datei config/INSTALLING an und öffne diese Seite erneut, um in den Installationsmodus zu gelangen.');
    } else {
        die('Antragsgrün is not configured yet. Please create the config/INSTALLING file and call this site again to open the installation wizard.');
    }
}

require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

try {
    $config = require(__DIR__ . '/../config/web.php');

    (new yii\web\Application($config))->run();
} catch (\yii\base\InvalidConfigException $e) {
    $error = htmlentities($e->getMessage(), ENT_COMPAT, 'UTF-8');
    $file  = ($_SERVER['ANTRAGSGRUEN_CONFIG'] ?? 'config/config.json');

    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && str_starts_with($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'de')) {
        if (str_contains($error, 'The table does not exist: site')) {
            $message = 'Die Seite wurde noch nicht korrekt eingerichtet. Zwar konnte eine Verbindung zur Datenbank hergestellt werdne, aber das Datenbankschema wurde noch nicht eingerichtet.';
            if (isset($_ENV['DB_HOST'])) {
                $message .= '<br><br>Falls dies eine frisch installierte Antragsgrün-Version auf Basis von Docker ist, kann dies daran liegen, dass der Installationsmodus nicht gestartet wurde. Dies kann über folgenden Befehl erfolgen (siehe <a href="https://github.com/CatoTH/antragsgruen#using-the-docker-image">README</a>):<br><pre>docker exec antragsgruen-web-1 /root/enable-installer.sh</pre>';
            }
        } else {
            $message = 'Leider ist die Antragsgrün-Konfigurationsdatei (%FILE%) fehlerhaft.
            Die Fehlermeldung lautet: %ERROR%<br><br>
            Du kannst auf folgende Weisen versuchen, sie zu korrigieren:<ul>
            <li>Die Datei von Hand bearbeiten und den Fehler ausfindig machen und korrigieren.</li>
            <li>Den Installationsmodus aktivieren (die Datei config/INSTALLING anlegen) und eine beliebige Seite aufrufen, um in den Installationsmodus zu gelangen.</li>
            </ul>';
        }
    } else {
        if (str_contains($error, 'The table does not exist: site')) {
            $message = 'The site was not yet set up correctly. While it is possible to connect to the database, the database tables have not yet been created.';
            if (isset($_ENV['DB_HOST'])) {
                $message .= '<br><br>If this is a fresh installation based on Docker, this may be because the installer mode is not active yet. This can be done (as per the <a href="https://github.com/CatoTH/antragsgruen#using-the-docker-image">README</a>) using the following command:<br><pre>docker exec antragsgruen-web-1 /root/enable-installer.sh</pre>';
            }
        } else {
            $message = 'Unfortunately, the configuration file (%FILE%) is invalid.
            The error message is: %ERROR%<br><br>
            You can try to fix it using one of these methods:<ul>
            <li>Find and fix the file by hand.</li>
            <li>Activate the installation mode (by creating the file config/INSTALLING) and open any page to enter the installation mode.</li>
            </ul>';
        }
    }

    echo str_replace(['%FILE%', '%ERROR%'], [$file, $error], $message);
}
