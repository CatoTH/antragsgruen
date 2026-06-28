<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeSectionDefinition
{
    public function __construct(
        public int $id,
        public MotionTypeSectionDefinitionType $type,
        public string $title,
        public MotionTypeSectionDefinitionRequired $required,
        public int $maxLen,
        public bool $lineNumbers,
        public bool $hasAmendments,
        public MotionTypeSectionDefinitionHasComments $hasComments,
        public bool $positionRight,
    ) {
    }
}
