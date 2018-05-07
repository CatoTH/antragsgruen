<?php

namespace app\components;

use app\models\db\Consultation;
use app\models\db\Motion;
use app\models\db\Site;
use app\models\db\User;
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
        $session->register('antragsgruen.rpc.getConsultationMotions', [$this, 'getConsultationMotions']);
    }

    /**
     * @param string $subdomain
     * @param string $urlPath
     * @return Consultation
     * @throws \Exception
     */
    protected static function getConsultation($subdomain, $urlPath)
    {
        $site = Site::findOne(['subdomain' => $subdomain]);
        if (!$site) {
            throw new \Exception('Site not found');
        }
        $consultation = array_filter($site->consultations, function (Consultation $con) use ($urlPath) {
            return $con->urlPath === $urlPath;
        });
        if (count($consultation) === 0) {
            throw new \Exception('Consultation not found');
        }
        return $consultation[0];
    }

    /**
     * @param string $auth
     * @return User|null
     */
    protected static function getUser($auth)
    {
        $auth = JwtAuthenticationProvider::decodeSignature($auth);
        if (!$auth) {
            return null;
        }
        $user = User::findOne(['auth' => $auth]);
        if ($user) {
            return $user;
        } else {
            return null;
        }
    }

    /**
     * @param $args
     * @return string
     */
    public function getMotion($args)
    {
        if (count($args) !== 4) {
            return 'Invalid number or arguments';
        }
        try {
            $user = static::getUser($args[0]); // @TODO check

            $consultation = static::getConsultation($args[1], $args[2]);
            $motion       = $consultation->getMotion($args[3]);
            if (!$motion) {
                throw new \Exception('Motion not found');
            }
            return json_encode([
                'success' => true,
                'data'    => $motion->getJsonObject(),
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param $args
     * @return string
     */
    public function getConsultationMotions($args, $more)
    {
        if (count($args) !== 3) {
            return 'Invalid number or arguments';
        }
        try {
            $user = static::getUser($args[0]); // @TODO check

            $consultation = static::getConsultation($args[1], $args[2]);
            return json_encode([
                'success' => true,
                'data'    => array_map(function (Motion $motion) {
                    return $motion->getJsonObject();
                }, $consultation->getVisibleMotionsSorted(true))
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
