<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechAppliedItem
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $isPointOfOrder,
        public string $appliedAt,
    ) {
    }

    public static function fromItem(\app\models\api\SpeechSubqueueItem $item): self
    {
        return new self(
            id: $item->id,
            name: $item->name,
            isPointOfOrder: $item->isPointOfOrder,
            appliedAt: $item->dateApplied->format('c'),
        );
    }
}
