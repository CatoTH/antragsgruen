<?php

namespace app\components\mail;

use app\models\exceptions\ServerConfiguration;

class SMTP extends Base
{
    private $host;
    private $port             = 25;
    private $name             = 'localhost';
    private $connectionClass  = null;
    private $connectionConfig = null;

    /**
     * @param array $params
     * @throws ServerConfiguration
     */
    public function __construct($params)
    {
        if (!isset($params['host'])) {
            throw new ServerConfiguration('host not set');
        }
        $this->host = $params['host'];

        if (isset($params['port'])) {
            $this->port = IntVal($params['port']);
        }
        if (isset($params['name'])) {
            $this->name = $params['name'];
        }

        if (!isset($params['authType'])) {
            throw new ServerConfiguration('authType not set');
        }
        switch ($params['authType']) {
            case 'none':
                break;
            case 'plain':
            case 'login':
            case 'crammd5':
                $this->connectionClass  = $params['authType'];
                $this->connectionConfig = [
                    'username' => $params['username'],
                    'password' => $params['password'],
                ];
                break;
            case 'plain_tls':
                $this->connectionClass  = 'plain';
                $this->connectionConfig = [
                    'username' => $params['username'],
                    'password' => $params['password'],
                    'ssl'      => 'tls',
                ];
                break;
            default:
                throw new ServerConfiguration('Unknown authType: ' . $params['authType']);
        }
    }

    /**
     * @param int $type
     * @return \Zend\Mail\Message
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getMessageClass($type)
    {
        return new \Zend\Mail\Message();
    }

    /**
     * @return \Zend\Mail\Transport\TransportInterface
     */
    public function getTransport()
    {
        $options = [
            'name' => $this->name,
            'host' => $this->host,
            'port' => $this->port,
        ];
        if ($this->connectionClass !== null) {
            $options['connection_class']  = $this->connectionClass;
            $options['connection_config'] = $this->connectionConfig;
        }
        $smtpOpts = new \Zend\Mail\Transport\SmtpOptions($options);
        $transport = new \Zend\Mail\Transport\Smtp();
        $transport->setOptions($smtpOpts);
        return $transport;
    }
}
