<?php

declare(strict_types=1);

namespace app\models\api\motionType;

class MotionTypeLabels
{
    public function __construct(
        public string $singular,
        public string $plural,
        public string $create,
    ) {
    }

    public static function fromEntity(\app\models\db\ConsultationMotionType $motionType): self
    {
        return new self(
            singular: $motionType->titleSingular,
            plural: $motionType->titlePlural,
            create: $motionType->createTitle,
        );
    }
}
