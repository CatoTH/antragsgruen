<?php

namespace app\components\updater;

use app\models\exceptions\Internal;
use app\models\exceptions\Network;

class UpdateChecker
{
    public static function isUpdaterAvailable(): bool
    {
        /** @phpstan-ignore-next-line */
        return (defined('ANTRAGSGRUEN_INSTALLATION_SOURCE') && ANTRAGSGRUEN_INSTALLATION_SOURCE === 'dist');
    }

    /**
     * @return Update[]
     * @throws Network
     * @throws Internal
     */
    public static function getAvailableUpdates(?string $version = null): array
    {
        if ($version === null) {
            $version = ANTRAGSGRUEN_VERSION;
        }

        $curlc = curl_init(ANTRAGSGRUEN_UPDATE_BASE . $version);
        if (!$curlc) {
            throw new Network('The update could not be loaded (curl cannot be initialized)');
        }
        curl_setopt($curlc, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curlc);
        $info = curl_getinfo($curlc);
        curl_close($curlc);

        if (!in_array($info['http_code'], [200, 404])) {
            throw new Network("The updates could not be loaded");
        }
        if ($info['http_code'] === 404) {
            return [];
        }
        if (!is_string($resp) || !$resp) {
            throw new Network("The updates could not be loaded (empty string returned)");
        }
        try {
            $json = json_decode($resp, true);
            if (!is_array($json)) {
                throw new Internal("The updates could not be processed: not an array");
            }
            $updates = [];
            foreach ($json as $update) {
                $updates[] = new Update($update);
            }
            return $updates;
        } catch (\Exception $e) {
            throw new Internal("The updates could not be processed: " . $e->getMessage());
        }
    }
}
