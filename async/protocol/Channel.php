<?php

namespace app\async\protocol;

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
        foreach ($this->sessions as $session) {
            try {
                $session->sendDataToClient($data);
            } catch (\Exception $e) {
                echo "Error sending data to session: " . $session->getId() . "\n";
                $this->removeSession($session);
            }
        }
    }
}
