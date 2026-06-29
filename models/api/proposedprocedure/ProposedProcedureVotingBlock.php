<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

class ProposedProcedureVotingBlock
{
    public function __construct(
        public ?string $id = null,
        public ?string $title = null,
        /** @var ProposedProcedureVotingItem[]|null */
        public ?array $items = null,
    ) {
    }
}
