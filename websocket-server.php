<?php

use Thruway\Peer\Router;
use Thruway\Transport\RatchetTransportProvider;

defined('YII_DEBUG') or define('YII_DEBUG', true);

// fcgi doesn't have STDIN and STDOUT defined by default
defined('STDIN') or define('STDIN', fopen('php://stdin', 'r'));
defined('STDOUT') or define('STDOUT', fopen('php://stdout', 'w'));

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

$yiiConfig = require(__DIR__ . '/config/console.php');
$yii = new yii\console\Application($yiiConfig);

$router = new Router();

$router->addInternalClient(new app\components\LiveClient());

$transportProvider = new RatchetTransportProvider("127.0.0.1", 9090);
$router->addTransportProvider($transportProvider);

$router->start();
