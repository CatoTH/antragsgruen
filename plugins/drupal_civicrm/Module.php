<?php

namespace app\plugins\drupal_civicrm;

use app\components\ExternalPasswordAuthenticatorInterface;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    /** @var PasswordAuthenticator */
    private static $authenticator = null;

    public static function getExternalPasswordAuthenticator(): ?ExternalPasswordAuthenticatorInterface
    {
        if (static::$authenticator === null) {
            $config = file_get_contents(__DIR__ . '/../../config/drupal_civicrm.json');
            $configuration = new PasswordAuthenticatorConfiguration($config);
            static::$authenticator = new PasswordAuthenticator($configuration);
        }
        return static::$authenticator;
    }
}
