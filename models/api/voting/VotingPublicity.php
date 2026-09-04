<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingPublicity
{
    public function __construct(
        public VotingVotesPublicity $singleVotes,
        public VotingResultsPublicity $results,
    ) {
    }
}
