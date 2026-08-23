<?php

declare(strict_types=1);

namespace app\models\api\debate;

class DebateSpeechQueueRequest
{
    public function __construct(
        public ?bool $activate = null,
    ) {
    }
}
