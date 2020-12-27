<?php

declare(strict_types=1);

namespace app\components;

use app\models\settings\AntragsgruenApp;

class CookieUser
{
    /** @var string */
    public $userToken;

    /** @var string */
    public $name;

    /** @var null|CookieUser */
    private static $userCache = null;

    public static function getFromCookieOrCache(): ?CookieUser
    {
        if (isset($_COOKIE['tempUser']) && preg_match('/^(?<token>[A-Za-z0-9_-]{32}),(?<name>.*)/', $_COOKIE['tempUser'], $matches)) {
            $cookieUser            = new CookieUser();
            $cookieUser->userToken = $matches['token'];
            $cookieUser->name      = trim($matches['name']);

            return $cookieUser;
        } elseif (static::$userCache) {
            return static::$userCache;
        } else {
            return null;
        }
    }

    public static function getFromCookieOrCreate(string $name): CookieUser
    {
        $existingUser = static::getFromCookieOrCache();
        if ($existingUser) {
            if (trim($name) !== $existingUser) {
                $existingUser->name = trim($name);
                $existingUser->sendCookie();
            }

            return $existingUser;
        }

        $newUser = new CookieUser();
        /** @noinspection PhpUnhandledExceptionInspection */
        $newUser->userToken = \Yii::$app->getSecurity()->generateRandomString(32);
        $newUser->name      = trim($name);
        $newUser->sendCookie();

        return $newUser;
    }

    public function sendCookie(): void
    {
        $content = $this->userToken . ',' . $this->name;
        /** @var AntragsgruenApp $params */
        $params = \Yii::$app->params;

        setcookie('tempUser', $content, time() + 365 * 24 * 3600, '/', ($params->cookieDomain ? $params->cookieDomain : ''));
    }
}
