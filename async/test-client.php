<?php
$cli = new \Swoole\Http\Client('127.0.0.1', 9501);
$cli->setHeaders(array('User-Agent' => 'swoole-http-client'));
$cli->setCookies(array('test' => 'value'));

$cli->post('/objects/1/', [
    'object' => 'motion',
    'data'   => [
        'id'    => 24,
        'title' => 'Mein Antrag',
    ],
], function (\Swoole\Http\Client $cli) {
    var_dump($cli);
    $cli->close();
});
