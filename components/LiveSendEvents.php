<?php

namespace app\components;

use app\models\db\Motion;
use Thruway\ClientSession;
use Thruway\Connection;

class LiveSendEvents
{
    protected static function triggerEvent(callable $onConnect)
    {
        $connection = new Connection(
            [
                "realm" => 'antragsgruen',
                "url"   => 'ws://127.0.0.1:9090',
            ]
        );
        $connection->on(
            'open',
            function (ClientSession $session) use ($connection, $onConnect) {
                $onConnect($session);
                $connection->close();
            }
        );
        $connection->open();
    }

    public static function motionChanged(Motion $motion)
    {
        static::triggerEvent(function (ClientSession $session) use ($motion) {
            $con = $motion->getMyConsultation();
            $topic = 'antragsgruen.topic.' . $con->site->subdomain . '.' . $con->urlPath . '.motions';
            $topic = str_replace('-', '_', $topic);
            $session->publish($topic, $motion->id, [], ["acknowledge" => true])->then(
                function () {
                    echo "Publish Acknowledged!\n";
                },
                function ($error) {
                    // publish failed
                    echo "Publish Error {$error}\n";
                }
            );
        });
    }
}