<?php

namespace app\plugins\speech_list_defaults;

class SpeechQueueSettings extends \app\models\settings\SpeechQueue
{
    public bool $preferNonspeaker = true;
    public bool $showNames = false;
}
