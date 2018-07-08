<?php

namespace app\components\async;

class ConventionListener
{
    /** @var \swoole_server */
    private $server;
    /** @var int */
    private $connection;

    /**
     * ConventionListener constructor.
     * @param \swoole_websocket_server $server
     * @param $connection
     */
    public function __construct(\swoole_websocket_server $server, $connection)
    {
        $this->server     = $server;
        $this->connection = $connection;
    }

    /**
     * @param mixed $data
     */
    public function processMessage($data)
    {
        //var_dump($this->server);
        //var_dump($this->fd)
        $this->server->push($this->connection, json_encode($data));
    }
}
