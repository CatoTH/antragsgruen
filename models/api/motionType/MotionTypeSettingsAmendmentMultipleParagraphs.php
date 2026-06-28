<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSettingsAmendmentMultipleParagraphs: string
{
    case MULTIPLE = 'multiple';
    case SINGLE_PARAGRAPH = 'single_paragraph';
    case SINGLE_CHANGE = 'single_change';

    public static function fromDbValue(int $value): self
    {
        return match ($value) {
            \app\models\db\ConsultationMotionType::AMEND_PARAGRAPHS_MULTIPLE => self::MULTIPLE,
            \app\models\db\ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_PARAGRAPH => self::SINGLE_PARAGRAPH,
            \app\models\db\ConsultationMotionType::AMEND_PARAGRAPHS_SINGLE_CHANGE => self::SINGLE_CHANGE,
            default => throw new \InvalidArgumentException('Unknown amendment multiple paragraphs value: ' . $value),
        };
    }
}
