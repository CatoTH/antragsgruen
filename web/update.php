<?php
require(__DIR__ . '/../vendor/autoload.php');

if (!file_exists(__DIR__ . '/../config/config.json')) {
    die("config.json not found");
}

$config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
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
    require(__DIR__ . '/../components/updater/view_enter_key.php');
    die();
}

$title = "Start update";
require(__DIR__ . '/../components/updater/layout-header.php');
$title = "Update";
echo "Update stub";
require(__DIR__ . '/../components/updater/layout-footer.php');
