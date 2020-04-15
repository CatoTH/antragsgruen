<?php

namespace app\models\settings;

class SpeechQueue implements \JsonSerializable
{
    use JsonConfigTrait;

    public $isOpen = false;
    public $preferNonspeaker = false;
}
