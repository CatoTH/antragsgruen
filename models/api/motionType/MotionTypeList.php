<?php

declare(strict_types=1);

namespace app\models\api\motionType;

use app\models\db\{Consultation, ConsultationMotionType};

class MotionTypeList
{
    public function __construct(
        /** @var MotionType[] */
        public array $items,
    ) {
    }

    public static function fromConsultation(Consultation $consultation): self
    {
        $motionTypes = $consultation->motionTypes;
        usort($motionTypes, fn(ConsultationMotionType $a, ConsultationMotionType $b) => $a->position <=> $b->position);

        return new self(array_map(
            fn(ConsultationMotionType $mt) => MotionType::fromEntity($mt),
            $motionTypes
        ));
    }
}
