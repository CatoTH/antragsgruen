<?php

declare(strict_types=1);

namespace app\models\majorityType;

use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use app\models\settings\VotingData;

abstract class IMajorityType
{
    // More yes- than no-votes
    public const MAJORITY_TYPE_SIMPLE = 1;

    // More yes- than no- and abstention-votes combined
    public const MAJORITY_TYPE_ABSOLUTE = 2;

    // At least 2/3 of all yes- and no-votes have to be yes (abstentions not counted)
    public const MAJORITY_TYPE_TWO_THIRD = 3;

    /**
     * @return array<class-string<IMajorityType>>
     */
    public static function getMajorityTypes(): array
    {
        $majorityTypes = [
            self::MAJORITY_TYPE_SIMPLE => SimpleMajority::class,
            self::MAJORITY_TYPE_ABSOLUTE => AbsoluteMajority::class,
            self::MAJORITY_TYPE_TWO_THIRD => TwoThirdsMajority::class,
        ];
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            foreach ($plugin::getAdditionalMajorityTypes() as $key => $val) {
                $majorityTypes[$key] = $val;
            }
        }
        return $majorityTypes;
    }

    abstract public function calculateResult(VotingData $votingData): int;

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
