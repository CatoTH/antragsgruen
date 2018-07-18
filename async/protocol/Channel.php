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
                if ($session->isActive()) {
                    $session->sendDataToClient([
                        'op'   => 'object',
                        'type' => $this->channelName,
                        'data' => $data,
                    ]);
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
        $cli->get('/std-parteitag/async/objects?channel=' . $this->channelName, function ($cli) use ($session) {
            if ($cli->statusCode === 200) {
                $session->sendDataToClient([
                    'op'   => 'object-collection',
                    'type' => $this->channelName,
                    'data' => json_decode($cli->body, true),
                ]);
            } else {
                var_dump($cli);
            }
        });
    }
}
