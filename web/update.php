<?php
require(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../config/defines.php');
require_once(__DIR__ . '/../components/updater/UpdateChecker.php');
require_once(__DIR__ . '/../components/updater/UpdateInformation.php');

$configFile = __DIR__ . '/../config/config.json';
if (!file_exists($configFile)) {
    die("config.json not found");
}

$config = json_decode(file_get_contents($configFile), true);
if (!isset($config['updateKey']) || strlen($config['updateKey']) < 10) {
    $title = "Not active";
    require(__DIR__ . '/../components/updater/layout-header.php');
    echo "Update mode is not active";
    require(__DIR__ . '/../components/updater/layout-footer.php');
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
    require(__DIR__ . '/../components/updater/view-enter-key.php');
    die();
}

// Starting here, the user is authenticated

if (isset($_POST['cancel_update'])) {
    $config = json_decode(file_get_contents($configFile), true);
    unset($config['updateKey']);
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    Header("Location: " . $config["domainPlain"]);
    die();
}

require(__DIR__ . '/../components/updater/available-updates.php');
die();
