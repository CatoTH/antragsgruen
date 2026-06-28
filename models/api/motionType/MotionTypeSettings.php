<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeSettings
{
    public function __construct(
        public bool $amendmentsOnly,
        public MotionTypeSettingsAmendmentMultipleParagraphs $amendmentMultipleParagraphs,
        public bool $hasProposedProcedure,
        public bool $hasResponsibilities,
        public bool $allowAmendmentsToAmendments,
        /** @var MotionTypeDeadlineEntry[] */
        public array $mergingDeadlines,
    ) {
    }
}
