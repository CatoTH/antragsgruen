<?php
// Set server variables needed by yii\web\Application in CLI mode
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../../web/index.php';
$_SERVER['SCRIPT_NAME'] = '/index.php';

$app = new yii\web\Application(require(dirname(__DIR__) . '/config/acceptance.php'));
$app->language = 'de';
