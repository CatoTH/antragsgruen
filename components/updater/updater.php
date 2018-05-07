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

$config = json_decode(file_get_contents($configFile), true);
if (!isset($config['updateKey']) || strlen($config['updateKey']) < 10) {
    $title = "Not active";
    require(__DIR__ . '/layout-header.php');
    echo "Update mode is not active";
    require(__DIR__ . '/layout-footer.php');
    die();
}
$updateKey = $config['updateKey'];

if (isset($_REQUEST['set_key'])) {
    setcookie('update_key', $_REQUEST['set_key'], 0, '/');
    $uri = explode('?', $_SERVER['REQUEST_URI']);
    header("Location: " . $uri[0]);
    die();
}

if (!isset($_COOKIE['update_key']) || $_COOKIE['update_key'] !== $updateKey) {
    require(__DIR__ . '/view-enter-key.php');
    die();
}

// Starting here, the user is authenticated

$errors = [];

if (isset($_POST['cancel_update'])) {
    $config = json_decode(file_get_contents($configFile), true);
    unset($config['updateKey']);
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    Header("Location: " . $config["domainPlain"]);
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

if (isset($_REQUEST['check_updates'])) {
    require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');
    $config      = require(__DIR__ . '/../../config/console.php');
    $application = new yii\console\Application($config);
    require(__DIR__ . '/available-migrations.php');
    die();
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
        $update->verifyFileIntegrity();
        $update->checkFilePermissions();
        $update->backupOldFiles(ANTRAGSGRUEN_VERSION);
        $update->performUpdate();
    } catch (\Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require(__DIR__ . '/available-updates.php');
die();
