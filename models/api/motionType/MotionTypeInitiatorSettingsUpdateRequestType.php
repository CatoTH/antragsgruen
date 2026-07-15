<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeInitiatorSettingsUpdateRequestType: string
{
    case ONLY_INITIATOR = 'only_initiator';
    case GIVEN_BY_INITIATOR = 'given_by_initiator';
    case COLLECTING_SUPPORTERS = 'collecting_supporters';
    case NO_INITIATOR = 'no_initiator';

    public function toSupportBaseValue(): int
    {
        return match ($this) {
            self::ONLY_INITIATOR => \app\models\supportTypes\SupportBase::ONLY_INITIATOR,
            self::GIVEN_BY_INITIATOR => \app\models\supportTypes\SupportBase::GIVEN_BY_INITIATOR,
            self::COLLECTING_SUPPORTERS => \app\models\supportTypes\SupportBase::COLLECTING_SUPPORTERS,
            self::NO_INITIATOR => \app\models\supportTypes\SupportBase::NO_INITIATOR,
        };
    }

    public static function fromSupportBaseValue(int $value): self
    {
        return match ($value) {
            \app\models\supportTypes\SupportBase::GIVEN_BY_INITIATOR => self::GIVEN_BY_INITIATOR,
            \app\models\supportTypes\SupportBase::COLLECTING_SUPPORTERS => self::COLLECTING_SUPPORTERS,
            \app\models\supportTypes\SupportBase::NO_INITIATOR => self::NO_INITIATOR,
            default => self::ONLY_INITIATOR,
        };
    }
}
