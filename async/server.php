<?php

require_once(__DIR__ . '/../models/settings/JsonConfigTrait.php');
require_once(__DIR__ . '/protocol/Channel.php');
require_once(__DIR__ . '/protocol/ProtocolHandler.php');
require_once(__DIR__ . '/protocol/InternalClient.php');
require_once(__DIR__ . '/protocol/Session.php');
require_once(__DIR__ . '/models/TransferrableObject.php');
require_once(__DIR__ . '/models/Userdata.php');
require_once(__DIR__ . '/models/Motion.php');


//$server = new swoole_websocket_server("0.0.0.0", 9501);
$server = new \Swoole\WebSocket\Server("127.0.0.1", 9501, SWOOLE_BASE);
//$server->addlistener('0.0.0.0', 9502, SWOOLE_SOCK_UDP);
$server->set([
    'worker_num'      => 1,
    'task_worker_num' => 1,
]);

$protocolHandler = new \app\async\protocol\ProtocolHandler();
$internalClient = new \app\async\protocol\InternalClient();



$server->on('handshake', [$protocolHandler, 'websocketHandshake']);
$server->on('open', [$protocolHandler, 'onOpen']);
$server->on('message', [$protocolHandler, 'onMessage']);

$server->on('close', function ($_server, $fd) {
    echo "client {$fd} closed\n";
});

$server->on('task', function ($_server, $worker_id, $task_id, $data) {
    var_dump($worker_id, $task_id, $data);
    return "hello world\n";
});

$server->on('finish', function ($_server, $task_id, $result) {
    var_dump($task_id, $result);
});

$server->on('packet', function ($_server, $data, $client) {
    echo "#" . posix_getpid() . "\tPacket {$data}\n";
    var_dump($client);
});

$server->on('request', [$internalClient, 'onRequest']);

$server->start();
