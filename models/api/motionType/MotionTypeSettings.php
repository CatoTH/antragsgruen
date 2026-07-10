<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\ConsultationMotionType;

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

    public static function fromEntity(ConsultationMotionType $motionType): self
    {
        $settings = $motionType->getSettingsObj();

        return new self(
            amendmentsOnly: (bool) $motionType->amendmentsOnly,
            amendmentMultipleParagraphs: MotionTypeSettingsAmendmentMultipleParagraphs::fromDbValue($motionType->amendmentMultipleParagraphs),
            hasProposedProcedure: $settings->hasProposedProcedure,
            hasResponsibilities: $settings->hasResponsibilities,
            allowAmendmentsToAmendments: $settings->allowAmendmentsToAmendments,
            mergingDeadlines: MotionTypeDeadlineEntry::fromDeadlineType($motionType, ConsultationMotionType::DEADLINE_MERGING),
        );
    }
}
