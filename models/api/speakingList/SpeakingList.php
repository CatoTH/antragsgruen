<?php

declare(strict_types=1);

namespace app\models\api\speakingList;

class SpeakingList
{
    public function __construct(
        public ?int $id = null,
        public ?bool $isOpen = null,
        public ?bool $haveApplied = null,
        public ?bool $requiresLogin = null,
        /** @var SpeakingListSubqueue[]|null */
        public ?array $subqueues = null,
        /** @var SpeakingListSlot[]|null */
        public ?array $slots = null,
        public ?int $currentTime = null,
        public ?int $speakingTime = null,
    ) {
    }
}
