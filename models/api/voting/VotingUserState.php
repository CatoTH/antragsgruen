<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingUserState
{
    public function __construct(
        public bool $eligible,
        public int $voteWeight,
        public bool $abstained,
        /** @var VotingUserVote[] */
        public array $votes,
        /** @var string[] */
        public array $canVoteGroupIds,
        public ?int $votesRemaining = null,
    ) {
    }
}
