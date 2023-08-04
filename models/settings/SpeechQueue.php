<?php

namespace app\models\settings;

use Symfony\Component\Serializer\Annotation\Ignore;

class SpeechQueue implements \JsonSerializable
{
    use JsonConfigTrait;

    public bool $isOpen = false;
    public bool $isOpenPoo = false;
    public bool $allowCustomNames = true; // @TODO Not used yet
    public bool $preferNonspeaker = false;
    public bool $showNames = true;
    /** @var null|int - in seconds */
    public ?int $speakingTime = null;

    /**
     * @Ignore()
     */
    public function getAdminApiObject(): array
    {
        return [
            'is_open' => $this->isOpen,
            'is_open_poo' => $this->isOpenPoo,
            'allow_custom_names' => $this->allowCustomNames,
            'prefer_nonspeaker' => $this->preferNonspeaker,
            'show_names' => $this->showNames,
            'speaking_time' => $this->speakingTime,
        ];
    }
}
