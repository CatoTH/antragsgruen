<?php

declare(strict_types=1);

namespace app\models\api\debate;

enum DebateItemTargetType: string
{
    case MOTION = 'motion';
    case AMENDMENT = 'amendment';
    case AGENDA_ITEM = 'agenda_item';
    case FREE_TEXT = 'free_text';
}
