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
    public const CHANNEL_VOTING = 'voting';

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
        self::ROLE_USER . '/' . self::CHANNEL_VOTING  => 3000,
        self::ROLE_ADMIN . '/' . self::CHANNEL_VOTING => 2000,
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
     * Collection channels carry a list whose members come and go (the votings): a poll answers with the
     * whole list, a live event with one member, and the widgets filter it themselves - which of the
     * votings a page shows is nothing the backend can decide for it.
     *
     * @return array{role: string, channel: string, poll_url: string, auth: string, interval: int, interval_configured: bool, key_placeholder: string|null, collection: bool}
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
            case self::ROLE_USER . '/' . self::CHANNEL_VOTING:
                // Every voting that is open, whether it belongs to a motion or not: which of them a
                // page shows is decided by the widget
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/voting/get-open-voting-blocks', 'assignedToMotionId' => '', 'showAllOpen' => 1]),
                    'auth'            => self::AUTH_JWT,
                    'key_placeholder' => null,
                    'collection'      => true,
                ];
                break;
            case self::ROLE_ADMIN . '/' . self::CHANNEL_VOTING:
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/voting/get-admin-voting-blocks']),
                    'auth'            => self::AUTH_JWT,
                    'key_placeholder' => null,
                    'collection'      => true,
                ];
                break;
            default:
                throw new Internal('Unknown live data channel: ' . $channelId);
        }

        return array_merge(
            ['role' => $role, 'channel' => $channel, 'collection' => false],
            self::getInterval($channelId),
            $config
        );
    }
}
