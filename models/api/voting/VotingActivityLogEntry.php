<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingActivityLogEntry
{
    public function __construct(
        public VotingActivityType $type,
        public string $date,
    ) {
    }
}
