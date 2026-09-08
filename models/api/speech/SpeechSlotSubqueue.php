<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechSlotSubqueue
{
    public function __construct(
        public string $name,
        public ?int $id = null,
    ) {
    }
}
