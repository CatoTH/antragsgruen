#!/usr/bin/env php
<?php

require_once(__DIR__ . '/../models/settings/JsonConfigTrait.php');
require_once(__DIR__ . '/protocol/Channel.php');
require_once(__DIR__ . '/protocol/ProtocolHandler.php');
require_once(__DIR__ . '/protocol/InternalClient.php');
require_once(__DIR__ . '/protocol/Session.php');
require_once(__DIR__ . '/protocol/Configuration.php');
require_once(__DIR__ . '/models/TransferrableObject.php');
require_once(__DIR__ . '/models/TransferrableChannelObject.php');
require_once(__DIR__ . '/models/Userdata.php');
require_once(__DIR__ . '/models/Motion.php');
require_once(__DIR__ . '/models/Amendment.php');

if (isset($_SERVER['ANTRAGSGRUEN_CONFIG'])) {
    $configFile = $_SERVER['ANTRAGSGRUEN_CONFIG'];
} elseif (isset($_ENV['ANTRAGSGRUEN_CONFIG'])) {
    $configFile = $_SERVER['ANTRAGSGRUEN_CONFIG'];
} else {
    $configFile = __DIR__ . '/../config/config.json';
}
$config = json_decode(file_get_contents($configFile), true);
if (!isset($config['asyncConfig'])) {
    die('Async mode is not configured');
}
$asyncConfig = new \app\async\protocol\Configuration($config['asyncConfig']);

$server = new \Swoole\WebSocket\Server("127.0.0.1", $asyncConfig->portInternal, SWOOLE_BASE);
$server->set([
    'worker_num'      => 1,
    'task_worker_num' => 1,
]);

$protocolHandler = new \app\async\protocol\ProtocolHandler($asyncConfig);
$internalClient = new \app\async\protocol\InternalClient($asyncConfig);
\app\async\protocol\Channel::$configuration = $asyncConfig;


$server->on('handshake', [$protocolHandler, 'websocketHandshake']);
$server->on('open', [$protocolHandler, 'onOpen']);
$server->on('message', [$protocolHandler, 'onMessage']);
$server->on('close', [$protocolHandler, 'onClose']);

$server->on('task', function ($_server, $worker_id, $task_id, $data) {
    var_dump($worker_id, $task_id, $data);
    return "hello world\n";
});

$server->on('finish', function ($_server, $task_id, $result) {
    var_dump($task_id, $result);
});

$server->on('packet', function ($_server, $data, $client) {
    if (function_exists('posix_getpid')) {
        echo "#" . posix_getpid() . "\tPacket {$data}\n";
    }
});

$server->on('request', [$internalClient, 'onRequest']);

$server->start();
