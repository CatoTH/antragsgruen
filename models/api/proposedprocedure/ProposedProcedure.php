<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

class ProposedProcedure
{
    public function __construct(
        /** @var ProposedProcedureOuterBlock[]|null */
        public ?array $proposedProcedure = null,
    ) {
    }
}
