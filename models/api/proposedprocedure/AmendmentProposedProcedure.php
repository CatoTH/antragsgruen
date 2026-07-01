<?php

declare(strict_types=1);

namespace app\models\api\proposedprocedure;

class AmendmentProposedProcedure
{
    public function __construct(
        public ?int $statusId = null,
        public ?string $statusTitle = null,
        /** @var \app\models\api\imotion\AmendmentSection[]|null */
        public ?array $sections = null,
    ) {
    }
}
