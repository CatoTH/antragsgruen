<?php

declare(strict_types=1);

namespace app\models\majorityType;

use app\models\exceptions\Internal;

abstract class IMajorityType
{
    // More yes- than no-votes
    const MAJORITY_TYPE_SIMPLE = 1;

    // More yes- than no- and abstention-votes combined
    const MAJORITY_TYPE_ABSOLUTE = 2;

    // At least 2/3 of all yes- and no-votes have to be yes (abstentions not counted)
    const MAJORITY_TYPE_TWO_THIRD = 3;

    /**
     * @return string[]|IMajorityType[]
     */
    public static function getMajorityTypes(): array
    {
        return [
            static::MAJORITY_TYPE_SIMPLE => SimpleMajority::class,
            static::MAJORITY_TYPE_ABSOLUTE => AbsoluteMajority::class,
            static::MAJORITY_TYPE_TWO_THIRD => TwoThirdsMajority::class,
        ];
    }

    abstract public function calculateResult(array $votes): int;

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
