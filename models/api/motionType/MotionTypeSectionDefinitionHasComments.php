<?php

declare(strict_types=1);

namespace app\models\api\motionType;

enum MotionTypeSectionDefinitionHasComments: string
{
    case NONE = 'none';
    case MOTION = 'motion';
    case PARAGRAPHS = 'paragraphs';
}
