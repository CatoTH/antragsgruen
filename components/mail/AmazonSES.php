<?php

declare(strict_types=1);

namespace app\components\mail;

use app\models\exceptions\ServerConfiguration;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;

class AmazonSES extends Base
{
    private string $accessKey;
    private string $secretKey;
    private string $region;

    /**
     * @throws ServerConfiguration
     */
    public function __construct(?array $params)
    {
        if (!isset($params['accessKey'])) {
            throw new ServerConfiguration('SES\'s accessKey not set');
        }
        if (!isset($params['secretKey'])) {
            throw new ServerConfiguration('SES\'s secretKey not set');
        }
        if (!isset($params['region'])) {
            throw new ServerConfiguration('SES\'s region not set');
        }
        $this->accessKey = $params['accessKey'];
        $this->secretKey = $params['secretKey'];
        $this->region = $params['region'];
    }

    protected function getTransport(): ?TransportInterface
    {
        $accessKey = urlencode($this->accessKey);
        $secretKey = urlencode($this->secretKey);
        $region = urlencode($this->region);

        return Transport::fromDsn('ses+api://' . $accessKey . ':' . $secretKey . '@default?region=' . $region);
    }
}
