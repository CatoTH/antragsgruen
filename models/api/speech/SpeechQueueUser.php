<?php

declare(strict_types=1);

namespace app\models\api\speech;

class SpeechQueueUser
{
    public function __construct(
        public int $id,
        public bool $isActive,
        public bool $isOpen,
        public bool $haveApplied,
        public bool $allowCustomNames,
        public bool $isOpenPoo,
        /** @var SpeechSubqueueUser[] */
        public array $subqueues,
        /** @var SpeechSlot[] */
        public array $slots,
        public bool $requiresLogin,
        public int $currentTime,
        public ?int $speakingTime = null,
    ) {
    }

    public static function fromEntity(
        \app\models\db\SpeechQueue $entity,
        ?\app\models\db\User $user,
        ?\app\components\CookieUser $cookieUser
    ): self {
        $queue = \app\models\api\SpeechQueue::fromEntity($entity);

        $subqueues = array_map(
            fn(\app\models\api\SpeechSubqueue $subqueue) => SpeechSubqueueUser::fromApiSubqueue($subqueue, $queue->settings->showNames, $user, $cookieUser),
            $queue->subqueues
        );

        $haveApplied = false;
        foreach ($subqueues as $subqueue) {
            if ($subqueue->haveApplied) {
                $haveApplied = true;
            }
        }

        return new self(
            id: $queue->id,
            isActive: $queue->isActive,
            isOpen: $queue->settings->isOpen,
            haveApplied: $haveApplied,
            allowCustomNames: $queue->settings->allowCustomNames,
            isOpenPoo: $queue->settings->isOpenPoo,
            subqueues: $subqueues,
            slots: array_map(fn(\app\models\api\SpeechQueueActiveSlot $slot) => SpeechSlot::fromApiSlot($slot), $queue->slots),
            requiresLogin: $queue->requiresLogin,
            currentTime: $queue->currentTime,
            speakingTime: $queue->settings->speakingTime,
        );
    }
}
