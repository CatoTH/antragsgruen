<?php

namespace app\components\mail;

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Sendmail extends Base
{
    protected function getTransport(): ?TransportInterface
    {
        return Transport::fromDsn('sendmail://default');

    }

    protected function getFallbackTransport(): ?TransportInterface
    {
        return Transport::fromDsn('sendmail://default?command=/usr/sbin/sendmail%20-t');
    }
}
