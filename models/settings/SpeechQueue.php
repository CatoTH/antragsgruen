<?php

namespace app\models\settings;

class SpeechQueue implements \JsonSerializable
{
    use JsonConfigTrait;

    public $isOpen = false;
    public $preferNonspeaker = false;
    public $showNames = true;

    public function getAdminApiObject(): array
    {
        return [
            'is_open' => $this->isOpen,
            'prefer_nonspeaker' => $this->preferNonspeaker,
            'show_names' => $this->showNames,
        ];
    }
}
