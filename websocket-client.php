<?php

require __DIR__ . '/vendor/autoload.php';

use Thruway\ClientSession;
use Thruway\Connection;


$onClose    = function ($msg) {
    echo $msg;
};
$connection = new Connection(
    [
        "realm"   => 'realm1',
        "onClose" => $onClose,
        "url"     => 'ws://127.0.0.1:9090',
    ]
);
$connection->on(
    'open',
    function (ClientSession $session) use ($connection) {
        $session->publish('example.topic', ['Hello, world from PHP!!!'], [], ["acknowledge" => true])->then(
            function () {
                echo "Publish Acknowledged!\n";
            },
            function ($error) {
                // publish failed
                echo "Publish Error {$error}\n";
            }
        );
        $connection->close();
    }
);
$connection->open();

/*
use Thruway\ClientSession;
use Thruway\Peer\Client;
use Thruway\Transport\PawlTransportProvider;

$client = new Client("realm1");
$client->addTransportProvider(new PawlTransportProvider("ws://127.0.0.1:9090/"));

$client->on('open', function (ClientSession $session) {

    // 1) subscribe to a topic
    $onevent = function ($args) {
        echo "Event {$args[0]}\n";
    };
    $session->subscribe('com.myapp.hello', $onevent);

    // 2) publish an event
    $session->publish('com.myapp.hello', ['Hello, world from PHP!!!'], [], ["acknowledge" => true])->then(
        function () {
            echo "Publish Acknowledged!\n";
        },
        function ($error) {
            // publish failed
            echo "Publish Error {$error}\n";
        }
    );

    // 3) register a procedure for remoting
    $add2 = function ($args) {
        return $args[0] + $args[1];
    };
    $session->register('com.myapp.add2', $add2);

    // 4) call a remote procedure
    $session->call('com.myapp.add2', [2, 3])->then(
        function ($res) {
            echo "Result: {$res}\n";
        },
        function ($error) {
            echo "Call Error: {$error}\n";
        }
    );
});


$client->start();
*/
