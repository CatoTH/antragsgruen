<?php

declare(strict_types=1);

namespace app\models\api\voting;

use app\models\db\VotingBlock;

enum VotingVotesPublicity: string
{
    case NOBODY = 'nobody';
    case ADMINS = 'admins';
    case EVERYBODY = 'everybody';

    public static function fromDbValue(?int $votesPublic): self
    {
        return match ($votesPublic) {
            VotingBlock::VOTES_PUBLIC_ALL => self::EVERYBODY,
            VotingBlock::VOTES_PUBLIC_ADMIN => self::ADMINS,
            // A voting that has never been opened has none set yet; opening it defaults to secret
            default => self::NOBODY,
        };
    }
}
