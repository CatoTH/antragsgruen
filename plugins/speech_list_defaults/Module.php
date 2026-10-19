<?php

namespace app\plugins\speech_list_defaults;

use app\models\db\SpeechQueue;
use app\plugins\ModuleBase;

class Module extends ModuleBase
{
    public static function getSpeechQueueSettingsClass(SpeechQueue $queue): string
    {
        return SpeechQueueSettings::class;
    }
}
