<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechSubqueueAdmin
{
    public function __construct(
        public int $id,
        public string $name,
        /** @var SpeechAdminItem[] */
        public array $onlist,
        /** @var SpeechAdminItem[] */
        public array $applied,
    ) {
    }

    public static function fromApiSubqueue(\app\models\api\SpeechSubqueue $subqueue): self
    {
        $applied = array_values(array_filter($subqueue->items, fn(\app\models\api\SpeechSubqueueItem $item): bool => $item->isApplication()));
        $onList = array_values(array_filter($subqueue->items, fn(\app\models\api\SpeechSubqueueItem $item): bool => $item->isOnList()));

        return new self(
            id: $subqueue->id,
            name: $subqueue->name,
            onlist: array_map(fn(\app\models\api\SpeechSubqueueItem $item) => SpeechAdminItem::fromItem($item), $onList),
            applied: array_map(fn(\app\models\api\SpeechSubqueueItem $item) => SpeechAdminItem::fromItem($item), $applied),
        );
    }
}
