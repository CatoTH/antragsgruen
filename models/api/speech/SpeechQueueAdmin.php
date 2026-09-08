<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechQueueAdmin
{
    public function __construct(
        public int $id,
        public bool $isActive,
        public SpeechQueueSettings $settings,
        /** @var SpeechSubqueueAdmin[] */
        public array $subqueues,
        /** @var SpeechSlot[] */
        public array $slots,
        public int $currentTime,
        public ?string $otherActiveName = null,
    ) {
    }

    public static function fromEntity(\app\models\db\SpeechQueue $entity): self
    {
        $queue = \app\models\api\SpeechQueue::fromEntity($entity);

        return new self(
            id: $queue->id,
            isActive: $queue->isActive,
            settings: SpeechQueueSettings::fromSettings($queue->settings),
            subqueues: array_map(fn(\app\models\api\SpeechSubqueue $subqueue) => SpeechSubqueueAdmin::fromApiSubqueue($subqueue), $queue->subqueues),
            slots: array_map(fn(\app\models\api\SpeechQueueActiveSlot $slot) => SpeechSlot::fromApiSlot($slot), $queue->slots),
            currentTime: $queue->currentTime,
            otherActiveName: $queue->otherActiveName?->get(),
        );
    }
}
