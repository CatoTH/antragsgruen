<?php

declare(strict_types=1);

namespace app\components;

use app\models\api\{agenda\AgendaList, debate\DebateState, SpeechQueue, voting\VotingPayloadBuilder};
use app\models\exceptions\Internal;
use app\models\db\{Consultation, VotingBlock};
use app\models\settings\AntragsgruenApp;
use GuzzleHttp\{Client, Exception\GuzzleException, RequestOptions};

class LiveTools
{
    /**
     * The configuration of the central LiveData JS module: the channels the widgets of this page need,
     * including how to poll them, and - if a Live server is configured - how to subscribe to them.
     *
     * The language is part of the subscription, not of the JWT: a live event is rendered in every
     * language of the consultation, and the Live server picks the one the destination asks for. The
     * JWT identifies the user, and the same user can have several browser tabs open in different
     * languages - a per-user language would mix those up (see docs/technical/live-data.md).
     *
     * @param array<array{role: string, channel: string}> $channels
     */
    public static function getJsConfig(Consultation $consultation, array $channels): array
    {
        $params = AntragsgruenApp::getInstance()->live;

        return [
            'channels' => array_map(
                fn (array $channel) => LiveDataChannels::getChannelConfig($channel['role'], $channel['channel']),
                $channels
            ),
            'live' => $params ? [
                'uri' => $params['wsUri'],
                'user_id' => JwtCreator::getCurrJwtUserId(),
                'installation' => $params['installationId'],
                'subdomain' => $consultation->site->subdomain,
                'consultation' => $consultation->urlPath,
                'language' => LanguageTools::getCurrentLanguage(),
            ] : null,
        ];
    }

    /**
     * @param array<string, string> $headers
     */
    public static function sendToRabbitMq(string $routingKey, string $data, array $headers = []): void
    {
        $params = AntragsgruenApp::getInstance()->live;
        $client = new Client(['base_uri' => $params['rabbitMqUri']]);

        $payload = json_encode([
            'properties' => ($headers ? ['headers' => $headers] : []),
            'routing_key' => $routingKey,
            'payload' => $data,
            'payload_encoding' => 'string',
        ], JSON_FORCE_OBJECT | JSON_THROW_ON_ERROR);

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
        self::sendToChannel($consultation, 'speech', $queue, $debug);
    }

    public static function sendDebate(Consultation $consultation, DebateState $debateState, bool $debug = false): void
    {
        self::sendToChannel($consultation, 'debate', $debateState, $debug);
    }

    public static function sendAgenda(Consultation $consultation, AgendaList $agenda, bool $debug = false): void
    {
        self::sendToChannel($consultation, 'agenda', $agenda, $debug);
    }

    /**
     * The state of one voting, as it is after something an administrator did: opened, closed, reset,
     * settings or items changed. Carries everything, including what belongs to each person alone.
     */
    public static function sendVotingState(Consultation $consultation, VotingBlock $block, bool $debug = false): void
    {
        self::sendVoting($consultation, $block, tallyOnly: false, debug: $debug);
    }

    /**
     * The counting of one voting, after a vote was cast: no configuration, and nothing about anyone
     * in particular - a vote changes the state of the person who cast it, and they were answered
     * directly (see docs/technical/voting-live-data.md §7).
     */
    public static function sendVotingTally(Consultation $consultation, VotingBlock $block, bool $debug = false): void
    {
        self::sendVoting($consultation, $block, tallyOnly: true, debug: $debug);
    }

    /**
     * Publishing must never be what makes a vote or an administrative action fail: the event is a
     * copy of a state that is already saved, and a broker that is down, misconfigured or without a
     * listener is a reason to log, not to answer the request with an error.
     */
    private static function sendVoting(Consultation $consultation, VotingBlock $block, bool $tallyOnly, bool $debug): void
    {
        if (!AntragsgruenApp::getInstance()->live) {
            return;
        }

        try {
            $envelope = VotingPayloadBuilder::fromVotingBlock($block)->buildLiveEnvelope($tallyOnly);
            $json = json_encode($envelope, JSON_THROW_ON_ERROR);

            if ($debug) {
                echo $json . "\n";
            }

            $params = AntragsgruenApp::getInstance()->live;
            $routingKey = 'voting.' . $params['installationId'] . '.' . $consultation->site->subdomain . '.' . $consultation->urlPath;

            self::sendToRabbitMq($routingKey, $json, [
                'default_language' => LanguageTools::getPrimaryLanguage($consultation),
            ]);
        } catch (\Throwable $e) {
            \Yii::error('Could not publish the voting event: ' . $e->getMessage(), __METHOD__);
        }
    }

    /**
     * Unlike a REST response, a live event is not rendered for the user triggering it, but for
     * everyone reading the consultation - who may well be browsing the site in a different language
     * than the moderator pressing the button, or than the console command sending the event.
     * Language-dependent strings are therefore serialized with all languages of the consultation
     * (see LocalizedString), and the Live server delivers the one matching each subscriber.
     * The language to use for subscribers whose language is not part of the message is passed along
     * as a message header.
     */
    private static function sendToChannel(Consultation $consultation, string $topic, object $payload, bool $debug): void
    {
        $params = AntragsgruenApp::getInstance()->live;
        if (!$params) {
            return;
        }

        $json = Tools::getSerializer()->serialize($payload, 'json', [
            LocalizedStringNormalizer::CONTEXT_ALL_LANGUAGES => true,
        ]);

        if ($debug) {
            echo $json . "\n";
        }

        $routingKey = $topic . '.' . $params['installationId'] . '.' . $consultation->site->subdomain . '.' . $consultation->urlPath;

        self::sendToRabbitMq($routingKey, $json, [
            'default_language' => LanguageTools::getPrimaryLanguage($consultation),
        ]);
    }
}
