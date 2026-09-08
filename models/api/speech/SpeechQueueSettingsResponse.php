<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechQueueSettingsResponse
{
    public function __construct(
        public SpeechQueueAdmin $queue,
        /** @var string[] */
        public array $sidebar,
    ) {
    }
}
