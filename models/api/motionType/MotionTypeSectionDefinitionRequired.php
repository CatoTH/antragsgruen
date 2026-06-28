<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSectionDefinitionRequired: string
{
    case NO = 'no';
    case YES = 'yes';
    case ENCOURAGED = 'encouraged';

    public static function fromDbValue(int $value): self
    {
        return match ($value) {
            \app\models\db\ConsultationSettingsMotionSection::REQUIRED_NO => self::NO,
            \app\models\db\ConsultationSettingsMotionSection::REQUIRED_YES => self::YES,
            \app\models\db\ConsultationSettingsMotionSection::REQUIRED_ENCOURAGED => self::ENCOURAGED,
            default => throw new \InvalidArgumentException('Unknown section required value: ' . $value),
        };
    }
}
