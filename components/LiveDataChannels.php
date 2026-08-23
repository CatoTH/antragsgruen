<?php

declare(strict_types=1);

namespace app\components;

use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;

/**
 * Registry of all data channels that widgets can subscribe to using the central LiveData JS module
 * (web/js/modules/shared/LiveData.js).
 *
 * A channel is the combination of a role (whose view of the data is meant) and a topic. Each channel
 * knows how its data can be polled from the REST API; if the Live server is configured, the same data
 * is pushed via websockets and polling is suspended. Views declare the channels they need using
 * Layout::addLiveDataChannel(); the resulting configuration is rendered into the "live-data-config"
 * meta tag by views/layouts/main.php.
 */
class LiveDataChannels
{
    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';

    public const CHANNEL_SPEECH = 'speech';
    public const CHANNEL_DEBATE = 'debate';

    // How the poll requests need to be authenticated: using a JWT (which requires $layout->provideJwt
    // to be set), or using a JWT if one is available (anonymous users get a response without
    // user-specific data).
    public const AUTH_JWT = 'jwt';
    public const AUTH_JWT_OPTIONAL = 'jwt-optional';

    private const KEY_PLACEHOLDER = 'QUEUEIDS';

    /**
     * How often a channel is polled (in milliseconds), if no Live server is connected. The defaults
     * can be overridden per channel using the "polling" configuration:
     * "polling": { "admin/speech": 2000 }
     */
    private const DEFAULT_INTERVALS = [
        self::ROLE_USER . '/' . self::CHANNEL_SPEECH  => 3000,
        self::ROLE_ADMIN . '/' . self::CHANNEL_SPEECH => 1000,
        self::ROLE_USER . '/' . self::CHANNEL_DEBATE  => 3000,
    ];

    /**
     * A configured interval is binding: widgets asking for more frequent updates (the fullscreen
     * projector does) are ignored then, so that the load caused by polling stays predictable.
     *
     * @return array{interval: int, interval_configured: bool}
     */
    private static function getInterval(string $channelId): array
    {
        $configured = AntragsgruenApp::getInstance()->polling[$channelId] ?? null;
        if ($configured !== null && intval($configured) > 0) {
            return ['interval' => intval($configured), 'interval_configured' => true];
        }

        return ['interval' => self::DEFAULT_INTERVALS[$channelId], 'interval_configured' => false];
    }

    /**
     * Channels with a key_placeholder address specific objects (currently: speaking lists). Widgets pass
     * the ID of the object they are interested in when registering; the JS module collects the IDs of
     * all registered widgets and substitutes them into that placeholder within the poll URL.
     *
     * @return array{role: string, channel: string, poll_url: string, auth: string, interval: int, interval_configured: bool, key_placeholder: string|null}
     */
    public static function getChannelConfig(string $role, string $channel): array
    {
        $channelId = $role . '/' . $channel;

        switch ($channelId) {
            case self::ROLE_USER . '/' . self::CHANNEL_SPEECH:
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/speech/get-queue', 'queueIds' => self::KEY_PLACEHOLDER]),
                    'auth'            => self::AUTH_JWT_OPTIONAL,
                    'key_placeholder' => self::KEY_PLACEHOLDER,
                ];
                break;
            case self::ROLE_ADMIN . '/' . self::CHANNEL_SPEECH:
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/speech/get-queue-admin', 'queueIds' => self::KEY_PLACEHOLDER]),
                    'auth'            => self::AUTH_JWT,
                    'key_placeholder' => self::KEY_PLACEHOLDER,
                ];
                break;
            case self::ROLE_USER . '/' . self::CHANNEL_DEBATE:
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/debate/index']),
                    'auth'            => self::AUTH_JWT_OPTIONAL,
                    'key_placeholder' => null,
                ];
                break;
            default:
                throw new Internal('Unknown live data channel: ' . $channelId);
        }

        return array_merge(['role' => $role, 'channel' => $channel], self::getInterval($channelId), $config);
    }
}
