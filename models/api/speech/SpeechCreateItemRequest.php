<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechCreateItemRequest
{
    public function __construct(
        public string $name,
        public ?int $subqueue = null,
    ) {
    }
}
