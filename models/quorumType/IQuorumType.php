<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\exceptions\Internal;
use app\models\settings\VotingData;

abstract class IQuorumType
{
    // No quorum
    const QUORUM_TYPE_NONE = 0;

    // At least half of eligible members are present
    const QUORUM_TYPE_HALF = 1;

    // At least 2/3 of all members are present
    const QUORUM_TYPE_TWO_THIRD = 3;

    /**
     * @return string[]|IQuorumType[]
     */
    public static function getQuorumTypes(): array
    {
        return [
            static::QUORUM_TYPE_NONE => NoQuorum::class,
            static::QUORUM_TYPE_HALF => AbsoluteMajority::class,
            static::QUORUM_TYPE_TWO_THIRD => TwoThirdsMajority::class,
        ];
    }

    abstract public function hasReachedQuorum(VotingData $votingData): bool;

    public static function getID(): int
    {
        throw new Internal('Cannot be called on the abstract base method');
    }

    public static function getDescription(): string
    {
        throw new Internal('Cannot be called on the abstract base method');
    }

    public static function getName(): string
    {
        throw new Internal('Cannot be called on the abstract base method');
    }
}
