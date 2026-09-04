<?php

declare(strict_types=1);

namespace app\models\api\voting;

use app\models\db\VotingBlock;

enum VotingResultsPublicity: string
{
    case ADMINS = 'admins';
    case EVERYBODY = 'everybody';

    public static function fromDbValue(?int $resultsPublic): self
    {
        // A voting that has never been opened has none set yet; opening it defaults to public
        return $resultsPublic === VotingBlock::RESULTS_PUBLIC_NO ? self::ADMINS : self::EVERYBODY;
    }
}
