<?php

namespace app\models\settings;

class SpeechQueue implements \JsonSerializable
{
    use JsonConfigTrait;

    /** @var bool */
    public $isOpen = false;
    /** @var bool */
    public $preferNonspeaker = false;
    /** @var bool */
    public $showNames = true;
    /** @var null|int - in seconds*/
    public $speakingTime = null;

    public function getAdminApiObject(): array
    {
        return [
            'is_open' => $this->isOpen,
            'prefer_nonspeaker' => $this->preferNonspeaker,
            'show_names' => $this->showNames,
            'speaking_time' => $this->speakingTime,
        ];
    }
}
