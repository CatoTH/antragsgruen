<?php

namespace app\components\mail;

use app\models\exceptions\ServerConfiguration;

class SMTP extends Base
{
    private $host;
    private $port = 25;
    private $name = 'localhost';
    private $authenticator = null;
    private $username = null;
    private $password = null;

    /**
     * @param array $params
     *
     * @throws ServerConfiguration
     */
    public function __construct($params)
    {
        if (!isset($params['host'])) {
            throw new ServerConfiguration('host not set');
        }
        $this->host = $params['host'];

        if (isset($params['port'])) {
            $this->port = intval($params['port']);
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
                $this->authenticator = 'LOGIN';
                $this->username      = $params['username'];
                $this->password      = $params['password'];
                break;
            case 'crammd5':
                $this->authenticator = 'CRAM-MD5';
                $this->username      = $params['username'];
                $this->password      = $params['password'];
                break;
            case 'plain_tls':
                $this->authenticator = 'PLAIN';
                $this->username      = $params['username'];
                $this->password      = $params['password'];
                break;
            default:
                throw new ServerConfiguration('Unknown authType: ' . $params['authType']);
        }
    }

    protected function getMessageClass($type)
    {
        return new \Swift_Message();
    }

    /**
     * @return \Swift_Mailer
     */
    protected function getTransport()
    {
        $encrypted = ($this->port !== 25);
        $transport = new \Swift_SmtpTransport($this->host, $this->port, ($encrypted ? 'ssl' : null));
        if ($this->username) {
            $transport->setUsername($this->username);
        }
        if ($this->password) {
            $transport->setPassword($this->password);
        }

        return new \Swift_Mailer($transport);
    }
}
