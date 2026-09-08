<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechItemOperationRequest
{
    public function __construct(
        public ?int $newSubqueueId = null,
        public ?int $position = null,
    ) {
    }
}
