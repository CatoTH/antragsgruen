<?php

namespace app\async\protocol;

use app\async\models\TransferrableChannelObject;
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

    /** @var array */
    protected $sentObjects = [];

    /**
     * Session constructor.
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
     * @param string $domain
     * @param string $channelName
     */
    public function addSubscribedChannel($domain, $channelName)
    {
        $this->subscribedChannels[]      = [$domain, $channelName];
        $this->sentObjects[$channelName] = [];
    }

    /**
     * @param mixed $data
     */
    public function sendDataToClient($data)
    {
        $this->server->push($this->connection, json_encode($data));
    }

    /**
     * @param string $channelName
     * @param TransferrableChannelObject $object
     */
    public function sendObjectToClient($channelName, $object)
    {
        $this->server->push($this->connection, json_encode([
            'op'     => 'object',
            'type'   => $channelName,
            'object' => $object,
        ]));
        $objectId = $object->getId();
        if (!in_array($objectId, $this->sentObjects[$channelName])) {
            $this->sentObjects[$channelName][] = $objectId;
        }
    }

    /**
     * @param string $channelName
     * @param string $objectId
     */
    public function deleteObjectFromClient($channelName, $objectId)
    {
        if (!in_array($objectId, $this->sentObjects[$channelName])) {
            return;
        }
        $this->server->push($this->connection, json_encode([
            'op'   => 'object-delete',
            'type' => $channelName,
            'id'   => $objectId,
        ]));
        $this->sentObjects[$channelName] = array_diff($this->sentObjects[$channelName], [$objectId]);
    }

    /**
     * @param string $channelName
     * @param TransferrableChannelObject[] $objects
     */
    public function sendObjectsToClient($channelName, $objects)
    {
        $this->server->push($this->connection, json_encode([
            'op'      => 'object-collection',
            'type'    => $channelName,
            'objects' => $objects,
        ]));
        foreach ($objects as $object) {
            $objectId = $object->getId();
            if (!in_array($objectId, $this->sentObjects[$channelName])) {
                $this->sentObjects[$channelName][] = $objectId;
            }
        }
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
