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

    public static function fromEntity(\app\models\db\ConsultationSettingsMotionSection $section): self
    {
        return new self(
            id: $section->id,
            type: MotionTypeSectionDefinitionType::fromTypeId($section->type),
            title: $section->title,
            required: MotionTypeSectionDefinitionRequired::fromDbValue($section->required),
            maxLen: $section->maxLen,
            lineNumbers: (bool) $section->lineNumbers,
            hasAmendments: (bool) $section->hasAmendments,
            hasComments: MotionTypeSectionDefinitionHasComments::fromDbValue($section->hasComments),
            positionRight: (bool) $section->positionRight,
        );
    }
}
