<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionType
{
    public function __construct(
        public int $id,
        public MotionTypeLabels $labels,
        public MotionTypeSettings $settings,
        public MotionTypePolicies $policies,
        /** @var MotionTypeSectionDefinition[] */
        public array $sections,
        public ?string $motionPrefix = null,
    ) {
    }
}
