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

    /** @var array<string, string[]> */
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
        self::$consultationListMapping = self::normalizeConsultationMapping($json['consultationMapping']);
    }

    /**
     * Each list name can be mapped to a single consultation URL or to an array of them.
     *
     * @return array<string, string[]>
     */
    private static function normalizeConsultationMapping(mixed $mapping): array
    {
        if (!is_array($mapping)) {
            throw new ConfigurationError('consultationMapping needs to be an object');
        }

        $normalized = [];
        foreach ($mapping as $listName => $consultationUrls) {
            if (is_string($consultationUrls)) {
                $consultationUrls = [$consultationUrls];
            }
            if (!is_array($consultationUrls) || count($consultationUrls) === 0) {
                throw new ConfigurationError('consultationMapping for ' . $listName . ' needs to be a string or a non-empty array of strings');
            }
            foreach ($consultationUrls as $consultationUrl) {
                if (!is_string($consultationUrl)) {
                    throw new ConfigurationError('consultationMapping for ' . $listName . ' needs to be a string or a non-empty array of strings');
                }
            }
            $normalized[(string)$listName] = array_values($consultationUrls);
        }

        return $normalized;
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
