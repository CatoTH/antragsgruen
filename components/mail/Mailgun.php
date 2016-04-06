<?php

namespace app\components\mail;

use app\models\db\EMailLog;
use app\models\exceptions\ServerConfiguration;

class Mailgun extends Base
{
    private $apiKey;
    private $domain;

    /**
     * @param array $params
     * @throws ServerConfiguration
     */
    public function __construct($params)
    {
        if (!isset($params['apiKey'])) {
            throw new ServerConfiguration('Mailgun\'s apiKey not set');
        }
        $this->apiKey = $params['apiKey'];
        $this->domain = $params['domain'];
    }


    /**
     * @param int $type
     * @return \Zend\Mail\Message
     */
    public function getMessageClass($type)
    {
        $message = new \SlmMail\Mail\Message\Mailgun();
        $message->addTag(EMailLog::$MANDRILL_TAGS[$type]);
        $message->setOptions([
            'tracking_clicks' => false,
            'tracking_opens'  => false,
            'tracking'        => false,
        ]);
        return $message;
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public function getTransport()
    {
        $client = new \Zend\Http\Client();
        $client->setAdapter(new \Zend\Http\Client\Adapter\Curl());

        $service = new \SlmMail\Service\MailgunService($this->domain, $this->apiKey);
        $service->setClient($client);
        $transport = new \SlmMail\Mail\Transport\HttpTransport($service);
        return $transport;
    }
}
