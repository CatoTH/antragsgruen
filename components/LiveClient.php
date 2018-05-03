<?php

namespace app\components;

use app\models\db\Site;
use React\EventLoop\LoopInterface;
use Thruway\Peer\Client;

class LiveClient extends Client
{
    /**
     * Constructor
     *
     * @param \React\EventLoop\LoopInterface $loop
     */
    public function __construct(LoopInterface $loop = null)
    {
        parent::__construct('antragsgruen', $loop);
    }

    /**
     * Handles session start
     *
     * @param \Thruway\ClientSession $session
     * @param \Thruway\Transport\TransportProviderInterface $transport
     */
    public function onSessionStart($session, $transport)
    {
        $session->register('antragsgruen.rpc.getMotion', [$this, 'getMotion']);
    }

    /**
     * @param $args
     * @return string
     */
    public function getMotion($args)
    {
        if (count($args) !== 1) {
            return 'Invalid number or arguments';
        }
        $site = Site::findOne(['subdomain' => $args[0]]);
        if ($site) {
            return json_encode($site->getAttributes());
        } else {
            return 'Site not found';
        }
    }
}
