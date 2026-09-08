<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingSettings
{
    public function __construct(
        public int $votesPublic,
        public int $resultsPublic,
        public int $votesNames,
        public int $answersTemplate,
        public ?int $majorityType = null,
        public ?int $quorumType = null,
        public ?int $votingTime = null,
        public ?int $assignedMotionId = null,
        public ?VotingPolicy $policy = null,
        /** @var VotingMaxVotes[]|null */
        public ?array $maxVotesByGroup = null,
    ) {
    }
}
