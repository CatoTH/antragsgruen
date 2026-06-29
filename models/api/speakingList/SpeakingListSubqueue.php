<?php

declare(strict_types=1);

namespace app\models\api\speakingList;

class SpeakingListSubqueue
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?int $numApplied = null,
        public ?bool $haveApplied = null,
        /** @var SpeakingListApplicant[]|null */
        public ?array $applied = null,
    ) {
    }
}
