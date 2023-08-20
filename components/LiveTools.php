<?php

declare(strict_types=1);

namespace app\components;

use app\models\api\SpeechQueue;
use app\models\settings\Privileges;
use app\models\exceptions\{ConfigurationError, Internal};
use app\models\db\{Consultation, User};
use app\models\settings\AntragsgruenApp;
use GuzzleHttp\{Client, Exception\GuzzleException, RequestOptions};

class LiveTools
{
    private const ROLE_SPEECH_ADMIN = 'ROLE_SPEECH_ADMIN';

    private static ?string $currUserId = null;

    public static function getCurrUserId(): string
    {
        if (!self::$currUserId) {
            if ($user = User::getCurrentUser()) {
                self::$currUserId = 'login-' . $user->id;
            } elseif ($cookieUser = CookieUser::getFromCookieOrCache()) {
                self::$currUserId = 'anonymous-'.$cookieUser->userToken;
            } else {
                self::$currUserId = 'anonymous-'.uniqid();
            }
        }

        return self::$currUserId;
    }

    public static function getJwtForCurrUser(Consultation $consultation): string
    {
        $userId = self::getCurrUserId();

        $roles = [];
        if (User::getCurrentUser()?->hasPrivilege($consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
            $roles[] = self::ROLE_SPEECH_ADMIN;
        }

        return JwtCreator::createJwt($consultation, $userId, $roles);
    }

    public static function getJsConfig(Consultation $consultation): array
    {
        $params = AntragsgruenApp::getInstance()->live;
        if (!$params) {
            throw new ConfigurationError('live settings not set');
        }

        return [
            'uri' => $params['wsUri'],
            'user_id' => self::getCurrUserId(),
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
        if (!AntragsgruenApp::getInstance()->live) {
            return;
        }

        $serializer = Tools::getSerializer();
        $json = $serializer->serialize($queue, 'json', ['live']);

        if ($debug) {
            echo $json . "\n";
        }

        $routingKey = 'speech.' . $consultation->site->subdomain . '.' . $consultation->urlPath;

        self::sendToRabbitMq($routingKey, $json);
    }
}
