<?php

namespace app\models\sitePresets;


use app\models\exceptions\Internal;

class SitePresets
{
    /** @var ISitePreset[] */
    public static $PRESETS = array(
        0 => "\\app\\models\\sitePresets\\Parteitag"
    );

    /**
     * @param int $presetId
     * @return ISitePreset
     * @throws Internal
     */
    public static function getPreset($presetId)
    {
        if (isset(static::$PRESETS[$presetId])) {
            return new static::$PRESETS[$presetId];
        }
        throw new Internal("Unknown Preset: " . $presetId);
    }
}
