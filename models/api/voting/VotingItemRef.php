<?php

declare(strict_types=1);

namespace app\models\api\voting;

class VotingItemRef
{
    public function __construct(
        public VotingItemType $type,
        public int $id,
    ) {
    }
}
