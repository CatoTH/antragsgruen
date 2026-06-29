<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

class ProposedProcedureVotingItem
{
    public function __construct(
        public ?ProposedProcedureVotingItemType $type = null,
        public ?int $id = null,
        public ?string $prefix = null,
        public ?string $titleWithPrefix = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
        public ?string $initiatorsHtml = null,
        public ?string $procedure = null,
    ) {
    }
}
