<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingEligibilityGroup
{
    public function __construct(
        public int $groupId,
        public \app\models\api\LocalizedString $title,
        /** @var VotingEligibilityUser[] */
        public array $users,
    ) {
    }
}
