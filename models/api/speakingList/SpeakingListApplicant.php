<?php

declare(strict_types=1);

namespace app\models\api\speakingList;

class SpeakingListApplicant
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?int $userId = null,
        public ?bool $isPointOfOrder = null,
        public ?string $appliedAt = null,
    ) {
    }
}
