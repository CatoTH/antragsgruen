<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingStatistics
{
    public function __construct(
        public int $votes,
        public int $voters,
    ) {
    }
}
