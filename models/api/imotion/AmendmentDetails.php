<?php

declare(strict_types=1);

namespace app\models\api\imotion;

class AmendmentDetails
{
    public function __construct(
        public ?string $type = null,
        public ?int $id = null,
        public ?string $prefix = null,
        public ?string $title = null,
        public ?string $titleWithPrefix = null,
        public ?int $firstLine = null,
        public ?int $statusId = null,
        public ?string $statusTitle = null,
        public ?string $datePublished = null,
        public ?MotionLink $motion = null,
        /** @var Supporter[]|null */
        public ?array $supporters = null,
        /** @var Supporter[]|null */
        public ?array $initiators = null,
        public ?string $initiatorsHtml = null,
        /** @var AmendmentSection[]|null */
        public ?array $sections = null,
        public ?\app\models\api\proposedprocedure\AmendmentProposedProcedure $proposedProcedure = null,
        public ?string $urlJson = null,
        public ?string $urlHtml = null,
    ) {
    }
}
