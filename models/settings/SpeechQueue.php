<?php

namespace app\models\settings;

class SpeechQueue implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $isOpen = false;
    public bool $preferNonspeaker = false;
    public bool $showNames = true;
    /** @var null|int - in seconds */
    public ?int $speakingTime = null;

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
