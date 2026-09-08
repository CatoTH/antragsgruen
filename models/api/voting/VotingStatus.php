<?php

declare(strict_types=1);

namespace app\models\api\voting;

use app\models\db\VotingBlock;

enum VotingStatus: string
{
    case OFFLINE = 'offline';
    case PREPARING = 'preparing';
    case OPEN = 'open';
    case CLOSED_PUBLISHED = 'closed_published';
    case CLOSED_UNPUBLISHED = 'closed_unpublished';

    public static function fromDbStatus(int $status): self
    {
        return match ($status) {
            VotingBlock::STATUS_PREPARING => self::PREPARING,
            VotingBlock::STATUS_OPEN => self::OPEN,
            VotingBlock::STATUS_CLOSED_PUBLISHED => self::CLOSED_PUBLISHED,
            VotingBlock::STATUS_CLOSED_UNPUBLISHED => self::CLOSED_UNPUBLISHED,
            default => self::OFFLINE,
        };
    }

    public function toDbStatus(): int
    {
        return match ($this) {
            self::PREPARING => VotingBlock::STATUS_PREPARING,
            self::OPEN => VotingBlock::STATUS_OPEN,
            self::CLOSED_PUBLISHED => VotingBlock::STATUS_CLOSED_PUBLISHED,
            self::CLOSED_UNPUBLISHED => VotingBlock::STATUS_CLOSED_UNPUBLISHED,
            self::OFFLINE => VotingBlock::STATUS_OFFLINE,
        };
    }
}
