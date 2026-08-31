<?php

declare(strict_types=1);

namespace app\models\api\debate;

class DebateVotingState
{
    public function __construct(
        public bool $canAdministerVotings,
        public bool $canUnassign,
        public DebateVotingStateCreateMode $createMode,
        /** @var DebateVotingBlockOption[] */
        public array $selectableVotingBlocks,
        public ?int $assignedVotingBlockId = null,
        public ?DebateVotingBlock $resolvedVotingBlock = null,
    ) {
    }
}
