<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingVoter
{
    public function __construct(
        public int $userId,
        /** @var int[] */
        public array $userGroupIds,
        public ?string $userName = null,
    ) {
    }
}
