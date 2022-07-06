<?php

declare(strict_types=1);

namespace app\plugins\gruene_ch_saml;

use app\components\LoginProviderInterface;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public const LOGIN_KEY = 'gruene-ch';
    public const AUTH_KEY_USERS = 'gruene-ch';

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
            $domainPlain . '/verts-login' => '/gruene_ch_saml/login/login',
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return array_merge(
            [
                $dom . 'verts-login' => '/gruene_ch_saml/login/login',
            ],
            parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld)
        );
    }
}
