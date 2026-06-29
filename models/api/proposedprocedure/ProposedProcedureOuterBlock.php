<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

class ProposedProcedureOuterBlock
{
    public function __construct(
        public ?string $title = null,
        /** @var ProposedProcedureVotingBlock[]|null */
        public ?array $votingBlocks = null,
    ) {
    }
}
