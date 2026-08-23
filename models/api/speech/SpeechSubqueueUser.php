<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechSubqueueUser
{
    public function __construct(
        public int $id,
        public string $name,
        public int $numApplied,
        public bool $haveApplied,
        /** @var SpeechAppliedItem[]|null */
        public ?array $applied = null,
    ) {
    }

    public static function fromApiSubqueue(
        \app\models\api\SpeechSubqueue $subqueue,
        bool $showNames,
        ?\app\models\db\User $user,
        ?\app\components\CookieUser $cookieUser
    ): self {
        /** @var \app\models\api\SpeechSubqueueItem[] $applied */
        $applied = array_values(array_filter($subqueue->items, fn(\app\models\api\SpeechSubqueueItem $item): bool => $item->isApplication()));

        // true if a matching (userID or userToken) user is on the list, but has not spoken yet
        $haveApplied = false;
        foreach ($applied as $item) {
            if (!$item->dateStarted && $item->isMe($user, $cookieUser)) {
                $haveApplied = true;
            }
        }

        return new self(
            id: $subqueue->id,
            name: $subqueue->name,
            numApplied: count($applied),
            haveApplied: $haveApplied,
            // Names are only exposed if the list is configured to show them
            applied: $showNames ? array_map(fn(\app\models\api\SpeechSubqueueItem $item) => SpeechAppliedItem::fromItem($item), $applied) : null,
        );
    }
}
