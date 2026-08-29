<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechSlot
{
    public function __construct(
        public int $id,
        public SpeechSlotSubqueue $subqueue,
        public string $name,
        public int $position,
        public ?string $dateStarted = null,
        public ?string $dateStopped = null,
        public ?string $dateApplied = null,
    ) {
    }

    public static function fromApiSlot(\app\models\api\SpeechQueueActiveSlot $slot): self
    {
        return new self(
            id: $slot->id,
            subqueue: new SpeechSlotSubqueue(name: $slot->subqueueName->get(), id: $slot->subqueueId),
            name: $slot->name,
            position: $slot->position,
            dateStarted: $slot->dateStarted?->format('c'),
            dateStopped: $slot->dateStopped?->format('c'),
            dateApplied: $slot->dateApplied?->format('c'),
        );
    }
}
