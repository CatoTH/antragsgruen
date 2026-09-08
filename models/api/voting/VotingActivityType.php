<?php

declare(strict_types=1);

namespace app\models\api\voting;

use app\models\db\VotingBlock;

enum VotingActivityType: string
{
    case OPENED = 'opened';
    case CLOSED = 'closed';
    case RESET = 'reset';
    case REOPENED = 'reopened';

    public static function fromDbType(int $type): self
    {
        return match ($type) {
            VotingBlock::ACTIVITY_TYPE_CLOSED => self::CLOSED,
            VotingBlock::ACTIVITY_TYPE_RESET => self::RESET,
            VotingBlock::ACTIVITY_TYPE_REOPENED => self::REOPENED,
            default => self::OPENED,
        };
    }
}
