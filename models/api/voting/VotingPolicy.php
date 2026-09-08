<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingPolicy
{
    public function __construct(
        public int $id,
        public ?\app\models\api\LocalizedString $description = null,
        /** @var int[]|null */
        public ?array $userGroups = null,
    ) {
    }
}
