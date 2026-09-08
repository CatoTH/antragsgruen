<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingSingleVote
{
    public function __construct(
        public string $answer,
        public int $weight,
        public VotingVoter $voter,
    ) {
    }
}
