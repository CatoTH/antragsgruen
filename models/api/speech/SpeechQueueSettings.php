<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechQueueSettings
{
    public function __construct(
        public bool $isOpen,
        public bool $isOpenPoo,
        public bool $allowCustomNames,
        public bool $preferNonspeaker,
        public bool $showNames,
        public ?int $speakingTime = null,
    ) {
    }

    public static function fromSettings(\app\models\settings\SpeechQueue $settings): self
    {
        return new self(
            isOpen: $settings->isOpen,
            isOpenPoo: $settings->isOpenPoo,
            allowCustomNames: $settings->allowCustomNames,
            preferNonspeaker: $settings->preferNonspeaker,
            showNames: $settings->showNames,
            speakingTime: $settings->speakingTime,
        );
    }
}
