<?php

namespace app\components\async;

use app\models\async\Userdata;

class ProtocolHandler
{
    public static function authenticate($cookie, callable $success, callable $error)
    {
        $cli = new \swoole_http_client('127.0.0.1', 80);
        $cli->setHeaders([
            'Host'            => 'stdparteitag.antragsgruen.local',
            'User-Agent'      => 'Swoole Client',
            'Accept'          => 'application/json,text/json',
            'Accept-Encoding' => 'gzip',
            'Cookie'          => 'PHPSESSID=' . $cookie,
        ]);
        $cli->get('/std-parteitag/async/user', function ($cli) use ($success, $error) {
            if ($cli->statusCode === 200) {
                $user = new Userdata($cli->body);
                $success($user);
            } else {
                $error($cli->statusCode, $cli->body);
            }
        });
    }
}
