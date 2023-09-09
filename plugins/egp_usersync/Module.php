<?php

declare(strict_types=1);

namespace app\plugins\egp_usersync;

use app\models\exceptions\ConfigurationError;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static string $webhookApiToken;

    /** @var string[] */
    public static array $webhookAllowedIps;

    /** @var array<string, string> */
    public static array $consultationListMapping = [];

    public function init(): void
    {
        parent::init();

        if (!file_exists(__DIR__ . '/../../config/egp_usersync.json')) {
            throw new ConfigurationError('egp_usersync.json not found');
        }
        $json = json_decode((string)file_get_contents(__DIR__ . '/../../config/egp_usersync.json'), true);
        if (!isset($json['apiToken']) || !isset($json['allowedIps']) || !isset($json['consultationMapping'])) {
            throw new ConfigurationError('apiToken, allowedIps or consultationMapping not found in configuration');
        }
        self::$webhookApiToken = $json['apiToken'];
        self::$webhookAllowedIps = $json['allowedIps'];
        self::$consultationListMapping = $json['consultationMapping'];
    }

    public static function getAllUrlRoutes(array $urls, string $dom, string $dommotion, string $dommotionOld, string $domamend, string $domamendOld): array
    {
        return array_merge(
            [
                $dom . 'webhook/usersync' => '/egp_usersync/usersync/usersync',
            ],
            parent::getAllUrlRoutes($urls, $dom, $dommotion, $dommotionOld, $domamend, $domamendOld)
        );
    }
}
