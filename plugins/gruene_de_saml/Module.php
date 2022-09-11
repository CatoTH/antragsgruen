<?php

declare(strict_types=1);

namespace app\plugins\gruene_de_saml;

use app\components\LoginProviderInterface;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public const AUTH_KEY_GROUPS = 'gruenesnetz';

    // This key is legacy from the time when we used OpenID as provider but wanted to keep the user accounts when switching to SAML.
    public const AUTH_KEY_USERS = 'openid';

    private static ?LoginProviderInterface $loginProvider = null;

    public static function getDedicatedLoginProvider(): ?LoginProviderInterface
    {
        if (self::$loginProvider === null) {
            self::$loginProvider = new SamlLogin();
        }
        return self::$loginProvider;
    }

    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        return [
            $domainPlain . '/gruene-login' => '/gruene_de_saml/login/login',
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return array_merge(
            [
                $dom . 'gruene-login' => '/gruene_de_saml/login/login',
            ],
            parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld)
        );
    }
}
