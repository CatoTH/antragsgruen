<?php

declare(strict_types=1);

namespace app\models\api\debate;

class DebateVotingAssignRequest
{
    public function __construct(
        public int $votingBlockId,
    ) {
    }
}
