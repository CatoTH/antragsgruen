<?php

require(__DIR__ . '/../../vendor/autoload.php');
require_once(__DIR__ . '/../../config/defines.php');
require_once(__DIR__ . '/UpdateChecker.php');
require_once(__DIR__ . '/Update.php');
require_once(__DIR__ . '/UpdatedFiles.php');

$configFile = __DIR__ . '/../../config/config.json';
if (!file_exists($configFile)) {
    die("config.json not found");
}
$config = json_decode((string)file_get_contents($configFile), true);

if (!\app\components\updater\UpdateChecker::isUpdaterAvailable()) {
    $title = 'Not available';
    require(__DIR__ . '/layout-header.php');
    echo "<div class='content'>The updater can only be used with downloaded packages.</div>";
    require(__DIR__ . '/layout-footer.php');
    die();
}

if (!isset($config['updateKey']) || strlen($config['updateKey']) < 10) {
    $title = 'Not active';
    require(__DIR__ . '/layout-header.php');
    echo "<div class='content'>Update mode is not active</div>";
    require(__DIR__ . '/layout-footer.php');
    die();
}
$updateKey = $config['updateKey'];

if (isset($_REQUEST['set_key'])) {
    setcookie('update_key', $_REQUEST['set_key'], 0, '/');
    $uri = explode('?', $_SERVER['REQUEST_URI']);
    header("Location: " . $uri[0], true, 302);
    die();
}

if (!isset($_COOKIE['update_key']) || $_COOKIE['update_key'] !== $updateKey) {
    require(__DIR__ . '/view-enter-key.php');
    die();
}

// Starting here, the user is authenticated

$errors  = [];
$success = [];

if (isset($_POST['cancel_update'])) {
    $config = json_decode((string)file_get_contents($configFile), true, JSON_THROW_ON_ERROR);
    unset($config['updateKey']);
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    Header('Location: ' . $config['resourceBase']);
    die();
}

if (isset($_POST['download_update'])) {
    try {
        foreach (\app\components\updater\UpdateChecker::getAvailableUpdates() as $update) {
            if ($update->version === $_POST['version']) {
                $update->download();
            }
        }
    } catch (\Exception $e) {
        $errors[] = $e->getMessage();
    }
}

if (isset($_POST['perform_update'])) {
    try {
        $update = null;
        foreach (\app\components\updater\UpdateChecker::getAvailableUpdates() as $upd) {
            if ($upd->version === $_POST['version']) {
                $update = $upd;
            }
        }
        if (!$update) {
            throw new \Exception('Update not found');
        }
        if (!$update->isDownloaded()) {
            throw new \Exception('Update has not been downloaded');
        }
        $update->verifyFileIntegrityAndPermissions(ANTRAGSGRUEN_VERSION);
        $update->backupOldFiles(ANTRAGSGRUEN_VERSION);
        $update->performUpdate();

        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        // Give Opcache & co some time to notice updated files
        $sleepTime = IntVal(ini_get('opcache.revalidate_freq'));
        if ($sleepTime > 10 || $sleepTime < 1) {
            $sleepTime = 1;
        }
        sleep($sleepTime);

        $url    = explode('?', $_SERVER['REQUEST_URI']);
        $newUrl = $url[0] . '?msg_updated=1';
        Header('Location: ' . $newUrl, true, 302);
        die();
    } catch (\Exception $e) {
        $errors[] = $e->getMessage();
    }
}

if (isset($_REQUEST['msg_updated'])) {
    $success[] = 'Antragsgrün has been updated. Please check below if a database upgrade is necessary. ' .
        'If so, please perform this upgrade before disabling the update mode again.<br><br>' .
        'Once everything is done, you can leave the update mode again.';
}

if (isset($_REQUEST['check_migrations'])) {
    require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
    $yiiConfig = require(__DIR__ . '/../../config/console.php');
    new yii\console\Application($yiiConfig);
    require(__DIR__ . '/available-migrations.php');
    die();
}

if (isset($_POST['perform_migrations'])) {
    require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
    $yiiConfig = require(__DIR__ . '/../../config/console.php');
    $config    = json_decode((string) file_get_contents($configFile), true);
    new yii\console\Application($yiiConfig);
    ob_start();
    \app\components\updater\MigrateHelper::performMigrations();
    \app\components\updater\MigrateHelper::flushCache();
    $output    = ob_get_clean();
    $success[] = 'The database has been updated.';
}

require(__DIR__ . '/available-updates.php');
die();
