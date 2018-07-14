<?php

namespace app\async\protocol;

class InternalClient
{
    /**
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response)
    {
        if ($request->server['remote_addr'] !== '127.0.0.1') {
            $response->status(403);
            $response->end('only localhost is allowed to post');
            return;
        }

        $request_uri = $request->server['request_uri'];

        if ($request->server['request_method'] === 'POST') {
            var_dump($request_uri);
            if (preg_match('/^\/objects\/(?<consultation>\d+)\/?$/siu', $request_uri, $matches)) {
                var_dump($request);
                var_dump($matches);
                $channel = Channel::getSpoolFromId(1, 'motions');
                $channel->sendToSessions($request->post);

                $response->status(201);
                $response->end('Notified all endpoints');
                return;
            }
        }

        $response->status(404);
        $response->end('Could not find the endpoint');
    }
}
