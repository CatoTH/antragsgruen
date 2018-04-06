<?php
require(__DIR__ . '/../vendor/autoload.php');

if (!file_exists(__DIR__ . '/../config/config.json')) {
    die("config.json not found");
}

$config = json_decode(file_get_contents(__DIR__ . '/../config/config.json'), true);
if (!isset($config['update_key']) || strlen($config['update_key']) < 10) {
    die("Update mode is not active");
}
$update_key = $config['update_key'];

if (isset($_REQUEST['set_key'])) {
    setcookie('update_key', $_REQUEST['set_key'], 0, '/');
    $uri = explode('?', $_SERVER['REQUEST_URI']);
    header("Location: " . $uri[0]);
    die();
}

if (!isset($_COOKIE['update_key']) || $_COOKIE['update_key'] !== $update_key) {
    require(__DIR__ . '/../components/updater/view_enter_key.php');
    die();
}

echo "Update stub";
