<?php

namespace app\async\protocol;

use app\async\models\TransferrableChannelObject;

class Channel
{
    /** @var Channel[] */
    protected static $channels = [];

    public static function getSpoolFromId($consultationId, $channelName)
    {
        if (!isset(static::$channels[$consultationId . '.' . $channelName])) {
            static::$channels[$consultationId . '.' . $channelName] = new Channel($consultationId, $channelName);
        }
        return static::$channels[$consultationId . '.' . $channelName];
    }

    /** @var int */
    private $consultationId;

    /** @var string */
    private $channelName;

    /** @var Session[] */
    private $sessions = [];

    /**
     * Spool constructor.
     * @param $consultationId
     * @param $channelName
     */
    public function __construct($consultationId, $channelName)
    {
        $this->consultationId = $consultationId;
        $this->channelName    = $channelName;
    }

    /**
     * @param Session $session
     */
    public function addSession(Session $session)
    {
        // @TODO Check eligibility
        $this->sessions[$session->getId()] = $session;
    }

    /**
     * @param Session $session
     */
    public function removeSession(Session $session)
    {
        unset($this->sessions[$session->getId()]);
    }

    /**
     * @param $data
     */
    public function sendToSessions($data)
    {
        $className = TransferrableChannelObject::$CHANNEL_CLASSES[$this->channelName];
        $object = new $className($data);

        foreach ($this->sessions as $session) {
            try {
                if ($session->isActive()) {
                    $session->sendObjectToClient($this->channelName, $object);
                } else {
                    echo "Session is not active anymore: " . $session->getId() . "\n";
                    Session::destroySession($session->getId());
                }
            } catch (\Exception $e) {
                echo "Error sending data to session: " . $session->getId() . "\n";
                Session::destroySession($session->getId());
            }
        }
    }

    /**
     * @param string $objectId
     */
    public function deleteFromSessions($objectId)
    {
        foreach ($this->sessions as $session) {
            try {
                if ($session->isActive()) {
                    $session->deleteObjectFromClient($this->channelName, $objectId);
                } else {
                    echo "Session is not active anymore: " . $session->getId() . "\n";
                    Session::destroySession($session->getId());
                }
            } catch (\Exception $e) {
                echo "Error deleting data from session: " . $session->getId() . "\n";
                Session::destroySession($session->getId());
            }
        }
    }

    /**
     * @param Session $session
     */
    public function loadInitialData(Session $session)
    {
        $cli = new \Swoole\Http\Client('127.0.0.1', 80);
        $cli->set(['timeout' => 3.0]);
        $cli->setHeaders([
            'Host'       => 'stdparteitag.antragsgruen.local',
            'User-Agent' => 'Swoole Client',
            'Accept'     => 'application/json',
        ]);
        $queryUrl = '/std-parteitag/async/objects?channel=' . urlencode($this->channelName);

        $cli->get($queryUrl, function ($cli) use ($session) {
            if ($cli->statusCode === 200) {
                $objectSrc = json_decode($cli->body, true);
                $className = TransferrableChannelObject::$CHANNEL_CLASSES[$this->channelName];

                $objects = array_map(function ($dat) use ($className) {
                    return new $className($dat);
                }, $objectSrc);

                $session->sendObjectsToClient($this->channelName, $objects);
            } else {
                var_dump($cli);
            }
        });
    }
}
