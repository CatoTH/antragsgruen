<?php

declare(strict_types=1);

namespace app\plugins\keycloak_oidc_login;

use app\components\LoginProviderInterface;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public const LOGIN_KEY = 'keycloak_oidc';
    public const AUTH_KEY_USERS = 'keycloak_oidc';

    private static ?LoginProviderInterface $loginProvider = null;

    public static function getDedicatedLoginProvider(): ?LoginProviderInterface
    {
        if (self::$loginProvider === null) {
            self::$loginProvider = new OidcLogin(
                'https://keycloak.domain.com',
                'antragsgruen.domain.com',
                'supderdupersecret'
            );
        }
        return self::$loginProvider;
    }

    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        return [
            $domainPlain . '/keycloak-oidc' => '/keycloak_oidc_login/login/login',
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return array_merge(
            [
                $dom . 'keycloak-oidc' => '/keycloak_oidc_login/login/login',
            ],
            parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld)
        );
    }
}
