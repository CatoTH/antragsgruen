<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeInitiatorsCanMergeAmendments: string
{
    case NEVER = 'never';
    case NO_COLLISION = 'no_collision';
    case WITH_COLLISION = 'with_collision';

    public function toDbValue(): int
    {
        return match ($this) {
            self::NEVER => \app\models\db\ConsultationMotionType::INITIATORS_MERGE_NEVER,
            self::NO_COLLISION => \app\models\db\ConsultationMotionType::INITIATORS_MERGE_NO_COLLISION,
            self::WITH_COLLISION => \app\models\db\ConsultationMotionType::INITIATORS_MERGE_WITH_COLLISION,
        };
    }
}
