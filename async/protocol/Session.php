<?php

namespace app\async\protocol;

use app\async\models\Userdata;

class Session
{
    /** @var Session[] */
    protected static $REGISTRY = [];

    /**
     * @param \Swoole\WebSocket\Server $_server
     * @param \Swoole\WebSocket\Frame $frame
     * @return Session
     */
    public static function getSessionForFd(\Swoole\WebSocket\Server $_server, \Swoole\WebSocket\Frame $frame)
    {
        if (!isset(static::$REGISTRY[$frame->fd])) {
            static::$REGISTRY[$frame->fd] = new Session($_server, $frame->fd);
        }
        return static::$REGISTRY[$frame->fd];
    }

    /**
     * @param int $fdNo
     */
    public static function destroySession($fdNo)
    {
        if (!isset(static::$REGISTRY[$fdNo])) {
            return;
        }
        $session = static::$REGISTRY[$fdNo];
        foreach ($session->subscribedChannels as $channelDef) {
            $channel = Channel::getSpoolFromId($channelDef[0], $channelDef[1]);
            $channel->removeSession($session);
        }
        unset(static::$REGISTRY[$fdNo]);
    }

    /** @var \swoole_server */
    private $server;
    /** @var int */
    private $connection;

    /** @var null|Userdata */
    protected $user = null;

    /** @var array */
    protected $subscribedChannels = [];

    /**
     * ConventionListener constructor.
     * @param \Swoole\WebSocket\Server $server
     * @param int $connection
     */
    public function __construct(\Swoole\WebSocket\Server $server, $connection)
    {
        $this->server     = $server;
        $this->connection = $connection;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->connection;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->server->exist($this->connection);
    }

    /**
     * @param int $consultationId
     * @param string $channelName
     */
    public function addSubscribedChannel($consultationId, $channelName)
    {
        $this->subscribedChannels[] = [$consultationId, $channelName];
    }

    /**
     * @param mixed $data
     */
    public function sendDataToClient($data)
    {
        $this->server->push($this->connection, json_encode($data));
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return ($this->user !== null);
    }

    /**
     * @param Userdata $user
     */
    public function setUser(Userdata $user)
    {
        $this->user = $user;
    }

    /**
     * @return Userdata|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
