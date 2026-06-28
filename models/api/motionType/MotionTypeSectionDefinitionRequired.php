<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSectionDefinitionRequired: string
{
    case NO = 'no';
    case YES = 'yes';
    case ENCOURAGED = 'encouraged';
}
