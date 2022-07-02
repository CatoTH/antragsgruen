<?php

declare(strict_types=1);

namespace app\plugins\gruene_ch_saml;

use app\components\LoginProviderInterface;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public const AUTH_KEY_USERS = 'gruene-ch';

    private static ?LoginProviderInterface $loginProvider = null;

    public static function getDedicatedLoginProvider(): ?LoginProviderInterface
    {
        if (self::$loginProvider === null) {
            self::$loginProvider = new SamlLogin();
        }
        return self::$loginProvider;
    }
}
