<?php

declare(strict_types=1);

namespace app\components;

use app\models\api\SpeechQueue;
use app\models\exceptions\{ConfigurationError, Internal};
use app\models\db\Consultation;
use app\models\settings\AntragsgruenApp;
use GuzzleHttp\{Client, Exception\GuzzleException, RequestOptions};

class LiveTools
{
    /**
     * @param array<array{role: string, channel: string}> $subscriptions
     */
    public static function getJsConfig(Consultation $consultation, array $subscriptions): array
    {
        $params = AntragsgruenApp::getInstance()->live;
        if (!$params) {
            throw new ConfigurationError('live settings not set');
        }

        return [
            'uri' => $params['wsUri'],
            'user_id' => JwtCreator::getCurrJwtUserId(),
            'installation' => $params['installationId'],
            'subdomain' => $consultation->site->subdomain,
            'consultation' => $consultation->urlPath,
            'subscriptions' => $subscriptions,
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

        try {
            $response = $client->request('POST', '/api/exchanges/%2f/' . urlencode($params['rabbitMqExchangeName']) . '/publish', [
                RequestOptions::AUTH => [$params['rabbitMqUsername'], $params['rabbitMqPassword']],
                RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
                RequestOptions::BODY => $payload,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            if (!$data['routed']) {
                throw new Internal('Could not send message - maybe no listener is running? ' . json_encode($data));
            }
        } catch (GuzzleException $e) {
            throw new Internal('Could not send message: ' . $e->getMessage());
        }
    }

    public static function sendSpeechQueue(Consultation $consultation, SpeechQueue $queue, bool $debug = false): void
    {
        $params = AntragsgruenApp::getInstance()->live;
        if (!$params) {
            return;
        }

        $serializer = Tools::getSerializer();
        $json = $serializer->serialize($queue, 'json', ['live']);

        if ($debug) {
            echo $json . "\n";
        }

        $routingKey = 'speech.' . $params['installationId'] . '.' . $consultation->site->subdomain . '.' . $consultation->urlPath;

        self::sendToRabbitMq($routingKey, $json);
    }
}
