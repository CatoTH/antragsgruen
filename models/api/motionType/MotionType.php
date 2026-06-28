<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\{ConsultationMotionType, ConsultationSettingsMotionSection};

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

    public static function fromEntity(ConsultationMotionType $motionType): self
    {
        $sections = array_map(
            fn(ConsultationSettingsMotionSection $section) => MotionTypeSectionDefinition::fromEntity($section),
            $motionType->motionSections
        );

        return new self(
            id: $motionType->id,
            labels: MotionTypeLabels::fromEntity($motionType),
            settings: MotionTypeSettings::fromEntity($motionType),
            policies: MotionTypePolicies::fromEntity($motionType),
            sections: $sections,
            motionPrefix: ($motionType->motionPrefix !== '' ? $motionType->motionPrefix : null),
        );
    }
}
