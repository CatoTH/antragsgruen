<?php

namespace app\components\async;

use app\models\async\Userdata;

class ProtocolHandler
{
    public function onMessage(\swoole_websocket_server $_server, $frame)
    {
        $data = json_decode($frame->data, true);
        if (!$data || !isset($data['op'])) {
            echo "Got invalid data package: " . $frame->data . "\n";
            return;
        }
        switch ($data['op']) {
            case 'auth':
                echo "Got auth string: " . $data['auth'] . "\n";
                $this->authenticate(
                    $data['auth'],
                    function (Userdata $ret) use ($_server, $frame) {
                        echo "\nSuccess: \n";
                        $_server->push($frame->fd, json_encode([
                            'op'   => 'auth_success',
                            'user' => $ret->toJSON(),
                        ]));
                    },
                    function ($ret, $err) use ($_server, $frame) {
                        echo "\nError: \n";
                        $_server->push($frame->fd, json_encode([
                            'op'  => 'auth_error',
                            'msg' => $err,
                        ]));
                    }
                );
                break;
        }
        return;

        var_dump($frame->data);
        echo "received " . strlen($frame->data) . " bytes\n";
        if ($frame->data == "close") {
            $_server->close($frame->fd);
        } elseif ($frame->data == "task") {
            $_server->task(['go' => 'die']);
        } else {
            //echo "receive from {$frame->fd}:{$frame->data}, opcode:{$frame->opcode}, finish:{$frame->finish}\n";
            // for ($i = 0; $i < 100; $i++)
            {
                $_send = str_repeat('B', rand(100, 800));
                $_server->push($frame->fd, $_send);
                // echo "#$i\tserver sent " . strlen($_send) . " byte \n";
            }
            $fd = $frame->fd;
            $_server->tick(2000, function ($id) use ($fd, $_server) {
                $_send = str_repeat('B', rand(100, 5000));
                $ret   = $_server->push($fd, $_send);
                if (!$ret) {
                    var_dump($id);
                    var_dump($_server->clearTimer($id));
                }
            });
        }
    }

    public function authenticate($cookie, callable $success, callable $error)
    {
        $cli = new \swoole_http_client('127.0.0.1', 80);
        $cli->set(['timeout' => 3.0]);
        $cli->setHeaders([
            'Host'       => 'stdparteitag.antragsgruen.local',
            'User-Agent' => 'Swoole Client',
            'Accept'     => 'application/json',
            'Cookie'     => 'PHPSESSID=' . $cookie,
        ]);
        $cli->get('/std-parteitag/async/user', function ($cli) use ($success, $error) {
            if ($cli->statusCode === 200) {
                $user = new Userdata($cli->body);
                $success($user);
            } else {
                var_dump($cli);
                $error($cli->statusCode, $cli->body);
            }
        });
    }

    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     * @return bool
     */
    public function websocketHandshake(\swoole_http_request $request, \swoole_http_response $response)
    {
        //自定定握手规则，没有设置则用系统内置的（只支持version:13的）
        if (!isset($request->header['sec-websocket-key'])) {
            //'Bad protocol implementation: it is not RFC6455.'
            $response->end();
            return false;
        }
        if (0 === preg_match('#^[+/0-9A-Za-z]{21}[AQgw]==$#', $request->header['sec-websocket-key'])
            || 16 !== strlen(base64_decode($request->header['sec-websocket-key']))
        ) {
            //Header Sec-WebSocket-Key is illegal;
            $response->end();
            return false;
        }

        $uuid    = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $key     = base64_encode(sha1($request->header['sec-websocket-key'] . $uuid, true));
        $headers = array(
            'Upgrade'               => 'websocket',
            'Connection'            => 'Upgrade',
            'Sec-WebSocket-Accept'  => $key,
            'Sec-WebSocket-Version' => '13',
            'KeepAlive'             => 'off',
        );
        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }
        $response->status(101);
        $response->end();
        global $server;
        $connId = $request->fd;
        $server->defer(function () use ($connId, $server) {
            $server->push($connId, json_encode(['op' => 'hello']));
        });

        Spool::$channels[$request->fd] = new ConventionListener($server, $request->fd);

        return true;
    }

    /**
     * @param \swoole_websocket_server $_server
     * @param \swoole_http_request $request
     */
    public function onOpen(\swoole_websocket_server $_server, \swoole_http_request $request)
    {
        echo "server#{$_server->worker_pid}: handshake success with fd#{$request->fd}\n";
        var_dump($_server->exist($request->fd), $_server->getClientInfo($request->fd));
//    var_dump($request);
        Spool::$channels[$request->fd] = $_server;
    }
}
