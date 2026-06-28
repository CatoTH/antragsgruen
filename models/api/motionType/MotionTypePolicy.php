<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypePolicy
{
    public function __construct(
        public MotionTypePolicyId $id,
        public string $description,
        /** @var MotionTypeDeadlineEntry[] */
        public array $deadlines,
        /** @var int[]|null */
        public ?array $userGroupIds = null,
    ) {
    }
}
