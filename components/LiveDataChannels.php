<?php

declare(strict_types=1);

namespace app\components;

use app\models\exceptions\Internal;

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

    // How the poll requests need to be authenticated: using the session cookie, using a JWT (which
    // requires $layout->provideJwt to be set), or using a JWT if one is available (anonymous users
    // get a response without user-specific data).
    public const AUTH_SESSION = 'session';
    public const AUTH_JWT = 'jwt';
    public const AUTH_JWT_OPTIONAL = 'jwt-optional';

    private const KEY_PLACEHOLDER = 'QUEUEIDS';

    /**
     * Channels with a key_placeholder address specific objects (currently: speaking lists). Widgets pass
     * the ID of the object they are interested in when registering; the JS module collects the IDs of
     * all registered widgets and substitutes them into that placeholder within the poll URL.
     *
     * @return array{role: string, channel: string, poll_url: string, auth: string, interval: int, key_placeholder: string|null}
     */
    public static function getChannelConfig(string $role, string $channel): array
    {
        switch ($role . '/' . $channel) {
            case self::ROLE_USER . '/' . self::CHANNEL_SPEECH:
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/speech/get-queue', 'queueIds' => self::KEY_PLACEHOLDER]),
                    'auth'            => self::AUTH_JWT_OPTIONAL,
                    'interval'        => 3000,
                    'key_placeholder' => self::KEY_PLACEHOLDER,
                ];
                break;
            case self::ROLE_ADMIN . '/' . self::CHANNEL_SPEECH:
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/speech/get-queue-admin', 'queueIds' => self::KEY_PLACEHOLDER]),
                    'auth'            => self::AUTH_JWT,
                    'interval'        => 1000,
                    'key_placeholder' => self::KEY_PLACEHOLDER,
                ];
                break;
            case self::ROLE_USER . '/' . self::CHANNEL_DEBATE:
                $config = [
                    'poll_url'        => UrlHelper::createUrl(['/rest/debate/index']),
                    'auth'            => self::AUTH_SESSION,
                    'interval'        => 3000,
                    'key_placeholder' => null,
                ];
                break;
            default:
                throw new Internal('Unknown live data channel: ' . $role . '/' . $channel);
        }

        return array_merge(['role' => $role, 'channel' => $channel], $config);
    }
}
