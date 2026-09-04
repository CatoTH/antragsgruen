<?php

declare(strict_types=1);

namespace app\models\api\debate;

use app\models\db\VotingBlock;

class DebateVotingBlock
{
    public function __construct(
        public int $id,
        public string $title,
        public int $status,
        public int $votesTotal,
        public int $votesUsers,
        public ?string $adminLink = null,
    ) {
    }

    public static function fromEntity(VotingBlock $block, ?string $adminLink): self
    {
        $stats = $block->getVoteStatistics();

        return new self(
            id: $block->id,
            title: $block->title,
            status: $block->votingStatus,
            votesTotal: $stats['votes'],
            votesUsers: $stats['users'],
            adminLink: $adminLink,
        );
    }
}
