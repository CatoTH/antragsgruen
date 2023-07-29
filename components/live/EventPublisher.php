<?php

declare(strict_types=1);

namespace app\components\live;

use app\models\settings\AntragsgruenApp;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class EventPublisher
{
    public static function sendToRabbitMq(string $routingKey, string $message): void
    {
        $client = new Client(['base_uri' => AntragsgruenApp::getInstance()->liveRabbitMqUri]);

        $payload = json_encode([
            'properties' => [],
            'routing_key' => $routingKey,
            'payload' => json_encode([
                "username" => $message,
            ]),
            'payload_encoding' => 'string',
        ], JSON_FORCE_OBJECT);

        $response = $client->request('POST', '/api/exchanges/%2f/antragsgruen-exchange/publish', [
            RequestOptions::AUTH => ['guest', 'guest'],
            RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
            RequestOptions::BODY => $payload,
        ]);

        echo $routingKey . "\n";
        echo $payload . "\n";

        echo $response->getBody()->getContents();
    }
}
