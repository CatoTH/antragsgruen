<?php

declare(strict_types=1);

namespace app\models\api\debate;

class DebateItemVotingBlock
{
    public function __construct(
        public int $id,
        public int $status,
        public string $title,
    ) {
    }
}
