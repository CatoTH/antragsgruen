<?php

declare(strict_types=1);

namespace app\components;

use app\models\exceptions\ConfigurationError;
use app\models\db\{Consultation, User};
use app\models\settings\AntragsgruenApp;
use GuzzleHttp\{Client, RequestOptions};

class LiveTools
{
    public static function getJsConfig(Consultation $consultation, User $user): array
    {
        $params = AntragsgruenApp::getInstance()->live;
        if (!$params) {
            throw new ConfigurationError('live settings not set');
        }

        return [
            'uri' => $params['wsUri'],
            'user_id' => $user->id,
            'subdomain' => $consultation->site->subdomain,
            'consultation' => $consultation->urlPath,
        ];
    }

    public static function sendToRabbitMq(string $routingKey, string $data): void
    {
        $params = AntragsgruenApp::getInstance()->live;
        $client = new Client(['base_uri' => $params['rabbitMqUri']]);

        $payload = json_encode([
            'properties' => [],
            'routing_key' => $routingKey,
            'payload' => $data,
            'payload_encoding' => 'string',
        ], JSON_FORCE_OBJECT);

        $response = $client->request('POST', '/api/exchanges/%2f/' . urlencode($params['rabbitMqExchangeName']) . '/publish', [
            RequestOptions::AUTH => [$params['rabbitMqUsername'], $params['rabbitMqPassword']],
            RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
            RequestOptions::BODY => $payload,
        ]);

        echo $routingKey . "\n";
        echo $payload . "\n";

        echo $response->getBody()->getContents();
    }
}
