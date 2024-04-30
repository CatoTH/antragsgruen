<?php

declare(strict_types=1);

namespace app\components\mail;

use app\models\db\{Consultation, EMailBlocklist, EMailLog};
use app\models\exceptions\ServerConfiguration;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class Mailjet extends Base
{
    private string $apiKey;
    private string $secret;

    /**
     * @throws ServerConfiguration
     */
    public function __construct(?array $params)
    {
        if (!isset($params['apiKey'])) {
            throw new ServerConfiguration('Mailjet\'s apiKey not set');
        }
        $this->apiKey = $params['apiKey'];
        $this->secret = $params['mailjetApiSecret'];
    }

    protected function getTransport(): ?TransportInterface
    {
        return Transport::fromDsn('mailjet+api://' . $this->apiKey . ':' . $this->secret . '@default');
    }
}
