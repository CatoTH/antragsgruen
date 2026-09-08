<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingAbstention
{
    public function __construct(
        public bool $enabled,
        public ?int $count = null,
        /** @var VotingVoter[]|null */
        public ?array $users = null,
    ) {
    }
}
