<?php

declare(strict_types=1);

namespace app\models\api\debate;

enum DebateVotingStateCreateMode: string
{
    case MOTION = 'motion';
    case AMENDMENT = 'amendment';
    case QUESTION = 'question';
    case NONE = 'none';
}
