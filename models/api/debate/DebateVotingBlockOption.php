<?php

declare(strict_types=1);

namespace app\models\api\debate;

use app\models\db\VotingBlock;

class DebateVotingBlockOption
{
    public function __construct(
        public int $id,
        public string $title,
        public int $status,
    ) {
    }

    public static function fromEntity(VotingBlock $block): self
    {
        return new self(
            id: $block->id,
            title: $block->title,
            status: $block->votingStatus,
        );
    }
}
