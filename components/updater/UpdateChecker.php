<?php

namespace app\components\updater;

use app\models\exceptions\Internal;
use app\models\exceptions\Network;

class UpdateChecker
{

    /**
     * @param null|string $version
     * @return Update[]
     * @throws Network
     * @throws Internal
     */
    public static function getAvailableUpdates($version = null)
    {
        if ($version === null) {
            $version = ANTRAGSGRUEN_VERSION;
        }

        $curlc = curl_init(ANTRAGSGRUEN_UPDATE_BASE . $version);
        curl_setopt($curlc, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curlc);
        $info = curl_getinfo($curlc);
        curl_close($curlc);

        if (!isset($info['http_code'])) {
            throw new Network("The updates could not be loaded");
        }
        if (!in_array($info['http_code'], [200, 404])) {
            throw new Network("The updates could not be loaded");
        }
        if ($info['http_code'] === 404) {
            return [];
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
