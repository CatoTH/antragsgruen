<?php

declare(strict_types=1);

namespace app\models\settings;

class IMotionStatus
{
    public function __construct(
        public int $id, // For plugin-defined IDs, use IDs of 100+
        public string $name, // e.g. "published"
        public ?string $nameVerb = null, // e.g. "publish"
        public ?bool $adminInvisible = false,
        public ?bool $userInvisible = false,
        public ?bool $motionProposedProcedureStatus = false,
        public ?bool $amendmentProposedProcedureStatus = false,
        public ?string $proposedProcedureName = null,
    ) {
    }
}
