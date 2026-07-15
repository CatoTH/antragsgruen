<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeContactRequirement: string
{
    case NONE = 'none';
    case OPTIONAL = 'optional';
    case REQUIRED = 'required';

    public function toDbValue(): int
    {
        return match ($this) {
            self::NONE => \app\models\settings\InitiatorForm::CONTACT_NONE,
            self::OPTIONAL => \app\models\settings\InitiatorForm::CONTACT_OPTIONAL,
            self::REQUIRED => \app\models\settings\InitiatorForm::CONTACT_REQUIRED,
        };
    }

    public static function fromDbValue(int $value): self
    {
        return match ($value) {
            \app\models\settings\InitiatorForm::CONTACT_OPTIONAL => self::OPTIONAL,
            \app\models\settings\InitiatorForm::CONTACT_REQUIRED => self::REQUIRED,
            default => self::NONE,
        };
    }
}
