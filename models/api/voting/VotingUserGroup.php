<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingUserGroup
{
    public function __construct(
        public int $id,
        public string $title,
        public int $memberCount,
    ) {
    }
}
