<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSettingsAmendmentMultipleParagraphs: string
{
    case MULTIPLE = 'multiple';
    case SINGLE_PARAGRAPH = 'single_paragraph';
    case SINGLE_CHANGE = 'single_change';
}
