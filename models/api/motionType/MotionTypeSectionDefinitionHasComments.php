<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSectionDefinitionHasComments: string
{
    case NONE = 'none';
    case MOTION = 'motion';
    case PARAGRAPHS = 'paragraphs';

    public static function fromDbValue(int $value): self
    {
        return match ($value) {
            \app\models\db\ConsultationSettingsMotionSection::COMMENTS_NONE => self::NONE,
            \app\models\db\ConsultationSettingsMotionSection::COMMENTS_MOTION => self::MOTION,
            \app\models\db\ConsultationSettingsMotionSection::COMMENTS_PARAGRAPHS => self::PARAGRAPHS,
            default => throw new \InvalidArgumentException('Unknown section has comments value: ' . $value),
        };
    }
}
