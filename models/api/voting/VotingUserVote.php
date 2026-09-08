<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingUserVote
{
    public function __construct(
        public string $groupId,
        public string $answer,
    ) {
    }
}
