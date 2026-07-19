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
                MotionTypeDeadlineEntry::fromDeadlineType($motionType, ConsultationMotionType::DEADLINE_MOTIONS),
                $motionType->getMotionPolicy()->checkCurrUserMotion()
            ),
            amendments: MotionTypePolicy::fromPolicy(
                $motionType->getAmendmentPolicy(),
                MotionTypeDeadlineEntry::fromDeadlineType($motionType, ConsultationMotionType::DEADLINE_AMENDMENTS),
                $motionType->getAmendmentPolicy()->checkCurrUserAmendment()
            ),
            comments: MotionTypePolicy::fromPolicy(
                $motionType->getCommentPolicy(),
                MotionTypeDeadlineEntry::fromDeadlineType($motionType, ConsultationMotionType::DEADLINE_COMMENTS),
                $motionType->getCommentPolicy()->checkCurrUserComment()
            ),
            supportMotions: MotionTypePolicy::fromPolicy(
                $motionType->getMotionSupportPolicy(),
                [],
                $motionType->getMotionSupportPolicy()->checkCurrUser()
            ),
            supportAmendments: MotionTypePolicy::fromPolicy(
                $motionType->getAmendmentSupportPolicy(),
                [],
                $motionType->getAmendmentSupportPolicy()->checkCurrUser()
            ),
        );
    }
}
