<?php

namespace app\components\async;

class InternalClient
{
    /**
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    public function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        if ($request->server['request_method'] === 'POST' && $request->server['remote_addr'] === '127.0.0.1') {
            foreach (Spool::$channels as $fd => $server) {
                echo "POST $fd\n";
                $server->processMessage("Test 1234" . rand(0, 10000));
            }
        }
    }
}
