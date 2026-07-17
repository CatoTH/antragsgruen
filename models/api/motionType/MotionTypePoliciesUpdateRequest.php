<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypePoliciesUpdateRequest
{
    public function __construct(
        public MotionTypePolicyUpdateRequest $motions,
        public MotionTypePolicyUpdateRequest $amendments,
        public MotionTypePolicyUpdateRequest $comments,
        public MotionTypePolicyUpdateRequest $supportMotions,
        public MotionTypePolicyUpdateRequest $supportAmendments,
    ) {
    }
}
