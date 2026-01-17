<?php

declare(strict_types=1);

namespace app\plugins\generic_sso;

use app\components\LoginProviderInterface;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public const AUTH_KEY_USERS = 'generic-sso';
    public const AUTH_KEY_GROUPS = 'generic-sso-groups';

    private static ?LoginProviderInterface $loginProvider = null;

    public function init(): void
    {
        parent::init();

        // Register translation category
        \Yii::$app->i18n->translations['generic_sso'] = [
            'class' => 'yii\i18n\PhpMessageSource',
            'basePath' => '@app/plugins/generic_sso/messages',
            'fileMap' => [
                'generic_sso' => 'generic_sso.php',
            ],
        ];
    }

    public static function getDedicatedLoginProvider(): ?LoginProviderInterface
    {
        if (self::$loginProvider === null) {
            self::$loginProvider = new SsoLogin();
        }
        return self::$loginProvider;
    }

    public static function getManagerUrlRoutes(string $domainPlain): array
    {
        return [
            $domainPlain . '/sso-login' => '/generic_sso/login/login',
            $domainPlain . '/sso-callback' => '/generic_sso/login/callback',
        ];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return array_merge(
            [
                $dom . 'sso-login' => '/generic_sso/login/login',
                $dom . 'sso-callback' => '/generic_sso/login/callback',
            ],
            parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld)
        );
    }
}
