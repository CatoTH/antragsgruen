<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingUserGroup
{
    public function __construct(
        public int $id,
        public \app\models\api\LocalizedString $title,
        public int $memberCount,
    ) {
    }
}
