<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechRegisterRequest
{
    public function __construct(
        public ?int $subqueue = null,
        public ?string $username = null,
        public ?bool $pointOfOrder = null,
    ) {
    }
}
