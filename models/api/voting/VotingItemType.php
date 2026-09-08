<?php

declare(strict_types=1);

namespace app\models\api\voting;

enum VotingItemType: string
{
    case MOTION = 'motion';
    case AMENDMENT = 'amendment';
    case QUESTION = 'question';
}
