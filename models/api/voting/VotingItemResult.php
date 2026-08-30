<?php

declare(strict_types=1);

namespace app\models\api\voting;

use app\models\db\IMotion;

enum VotingItemResult: string
{
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case QUORUM_REACHED = 'quorum_reached';
    case QUORUM_MISSED = 'quorum_missed';

    /**
     * Null for everything that is not an outcome - most importantly for an item that is still being
     * voted on.
     */
    public static function fromDbStatus(?int $status): ?self
    {
        return match ($status) {
            IMotion::STATUS_ACCEPTED => self::ACCEPTED,
            IMotion::STATUS_REJECTED => self::REJECTED,
            IMotion::STATUS_QUORUM_REACHED => self::QUORUM_REACHED,
            IMotion::STATUS_QUORUM_MISSED => self::QUORUM_MISSED,
            default => null,
        };
    }
}
