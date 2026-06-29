<?php

declare(strict_types=1);

namespace app\models\api\speakingList;

class SpeakingListSlot
{
    public function __construct(
        public ?int $id = null,
        public ?object $subqueue = null,
        public ?string $name = null,
        public ?int $userId = null,
        public ?int $position = null,
        public ?string $dateStarted = null,
        public ?string $dateStopped = null,
        public ?string $dateApplied = null,
    ) {
    }
}
