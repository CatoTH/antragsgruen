<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\ConsultationMotionType;

class MotionTypePolicies
{
    public function __construct(
        public MotionTypePolicy $motions,
        public MotionTypePolicy $amendments,
        public MotionTypePolicy $comments,
        public MotionTypePolicy $supportMotions,
        public MotionTypePolicy $supportAmendments,
    ) {
    }

    public static function fromEntity(ConsultationMotionType $motionType): self
    {
        return new self(
            motions: MotionTypePolicy::fromPolicy(
                $motionType->getMotionPolicy(),
                MotionTypeDeadlineEntry::fromDeadlineType($motionType, ConsultationMotionType::DEADLINE_MOTIONS)
            ),
            amendments: MotionTypePolicy::fromPolicy(
                $motionType->getAmendmentPolicy(),
                MotionTypeDeadlineEntry::fromDeadlineType($motionType, ConsultationMotionType::DEADLINE_AMENDMENTS)
            ),
            comments: MotionTypePolicy::fromPolicy(
                $motionType->getCommentPolicy(),
                MotionTypeDeadlineEntry::fromDeadlineType($motionType, ConsultationMotionType::DEADLINE_COMMENTS)
            ),
            supportMotions: MotionTypePolicy::fromPolicy($motionType->getMotionSupportPolicy(), []),
            supportAmendments: MotionTypePolicy::fromPolicy($motionType->getAmendmentSupportPolicy(), []),
        );
    }
}
