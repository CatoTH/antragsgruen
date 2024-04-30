<?php

namespace app\components\mail;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Sendmail extends Base
{
    private string $dsn = 'sendmail://default';

    public function __construct(?array $params)
    {
        if (isset($params['dsn'])) {
            $this->dsn = $params['dsn'];
        }
    }

    protected function getTransport(): ?TransportInterface
    {
        return Transport::fromDsn($this->dsn);

    }

    protected function getFallbackTransport(): ?TransportInterface
    {
        return Transport::fromDsn('sendmail://default?command=/usr/sbin/sendmail%20-t');
    }
}
