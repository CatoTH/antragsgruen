<?php

declare(strict_types=1);

namespace app\models\api\motionType;

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
}
