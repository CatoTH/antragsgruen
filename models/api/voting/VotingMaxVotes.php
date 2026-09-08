<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingMaxVotes
{
    public function __construct(
        public int $maxVotes,
        public ?int $groupId = null,
    ) {
    }
}
