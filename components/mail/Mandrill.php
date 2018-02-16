<?php

namespace app\components\mail;

use app\models\db\EMailLog;
use app\models\exceptions\ServerConfiguration;

class Mandrill extends Base
{
    private $apiKey;

    /**
     * @param array $params
     * @throws ServerConfiguration
     */
    public function __construct($params)
    {
        if (!isset($params['apiKey'])) {
            throw new ServerConfiguration('Mandrill\'s apiKey not set');
        }
        $this->apiKey = $params['apiKey'];
    }


    /**
     * @param int $type
     * @return \Zend\Mail\Message
     */
    protected function getMessageClass($type)
    {
        $message = new \SlmMail\Mail\Message\Mandrill();
        $message->addTag(EMailLog::$MANDRILL_TAGS[$type]);
        $message->setOptions([
            'important'         => false,
            'track_clicks'      => false,
            'track_opens'       => false,
            'inline_css'        => true,
            'view_content_link' => false,
        ]);
        return $message;
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    protected function getTransport()
    {
        $client = new \Zend\Http\Client();
        $client->setAdapter(new \Zend\Http\Client\Adapter\Curl());
        $service = new \SlmMail\Service\MandrillService($this->apiKey);
        $service->setClient($client);
        return new \SlmMail\Mail\Transport\HttpTransport($service);
    }
}
