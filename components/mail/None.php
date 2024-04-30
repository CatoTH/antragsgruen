<?php

declare(strict_types=1);

namespace app\components\mail;

use app\models\db\EMailLog;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

class None extends Base
{
    protected function getTransport(): ?TransportInterface
    {
        return null;
    }

    public function send(Email $message, string $toEmail): int
    {
        return EMailLog::STATUS_SKIPPED_OTHER;
    }
}
