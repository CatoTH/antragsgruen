<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechAdminItem
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isPointOfOrder,
        public string $appliedAt,
        public ?int $userId = null,
        public ?string $userToken = null,
    ) {
    }

    public static function fromItem(\app\models\api\SpeechSubqueueItem $item): self
    {
        return new self(
            id: $item->id,
            name: $item->name,
            isPointOfOrder: $item->isPointOfOrder,
            appliedAt: $item->dateApplied->format('c'),
            userId: $item->userId,
            userToken: $item->userToken,
        );
    }
}
