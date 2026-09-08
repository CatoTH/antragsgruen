<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingResults
{
    public function __construct(
        /** @var VotingOrganizationResult[] */
        public array $counts,
        public ?VotingItemGroupQuorum $quorum = null,
    ) {
    }
}
