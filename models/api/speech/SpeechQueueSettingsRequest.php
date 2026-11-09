<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechQueueSettingsRequest
{
    public function __construct(
        public bool $isActive,
        public bool $isOpen,
        public bool $isOpenPoo,
        public bool $preferNonspeaker,
        public bool $allowCustomNames,
        public bool $showNames,
        public ?int $speakingTime = null,
    ) {
    }
}
