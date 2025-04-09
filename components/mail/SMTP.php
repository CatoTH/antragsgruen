<?php

declare(strict_types=1);

namespace app\components\mail;

use app\models\exceptions\ServerConfiguration;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class SMTP extends Base
{
    private string $host;
    private int $port = 25;
    private ?string $username = null;
    private ?string $password = null;
    /** @phpstan-ignore-next-line  */
    private ?string $encryption = null;

    /**
     * @throws ServerConfiguration
     */
    public function __construct(?array $params)
    {
        if (!isset($params['host'])) {
            throw new ServerConfiguration('host not set');
        }
        $this->host = $params['host'];

        if (isset($params['port'])) {
            $this->port = intval($params['port']);
        }
        if (isset($params['encryption'])) {
            $this->encryption = $params['encryption'];
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
            case 'plain_tls':
                $this->username      = $params['username'];
                $this->password      = $params['password'];
                break;
            default:
                throw new ServerConfiguration('Unknown authType: ' . $params['authType']);
        }
    }

    protected function getTransport(): TransportInterface
    {
        /*
        $encrypted = ($this->port !== 25);
        if ($encrypted && $this->encryption !== null) {
            $encryption = $this->encryption;
        } else {
            $encryption = ($encrypted ? 'ssl' : null);
        }
        $transport = new \Swift_SmtpTransport($this->host, $this->port, $encryption);
        if ($this->username) {
            $transport->setUsername($this->username);
        }
        if ($this->password) {
            $transport->setPassword($this->password);
        }

        return new \Swift_Mailer($transport);
        */
        if ($this->username && $this->password) {
            $dsn = 'smtp://' . urlencode($this->username) . ':' . urlencode($this->password) . '@' .
                urlencode($this->host) . ':' . $this->port;
        } else {
            $dsn = 'smtp://' . urlencode($this->host) . ':' . $this->port;
        }

        return Transport::fromDsn($dsn);
    }
}
