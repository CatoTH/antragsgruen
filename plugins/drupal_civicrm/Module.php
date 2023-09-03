<?php

namespace app\plugins\drupal_civicrm;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    private static ?PasswordAuthenticator $authenticator = null;

    public static function getExternalPasswordAuthenticator(): ?ExternalPasswordAuthenticatorInterface
    {
        if (self::$authenticator === null) {
            $config = file_get_contents(__DIR__ . '/../../config/drupal_civicrm.json');
            $configuration = new PasswordAuthenticatorConfiguration($config);
            self::$authenticator = new PasswordAuthenticator($configuration);
        }
        return self::$authenticator;
    }
}
