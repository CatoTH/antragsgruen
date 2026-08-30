<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingEligibilityUser
{
    public function __construct(
        public int $userId,
        public string $userName,
        public int $weight,
    ) {
    }
}
