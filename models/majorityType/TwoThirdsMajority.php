<?php

declare(strict_types=1);

namespace app\models\majorityType;

use app\models\settings\VotingData;
use app\models\db\{IMotion, Vote};

class TwoThirdsMajority extends IMajorityType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'majority_twothirds');
    }

    public static function getDescription(): string
    {
        return \Yii::t('voting', 'majority_twothirds_h');
    }

    public static function getID(): int
    {
        return SimpleMajority::MAJORITY_TYPE_TWO_THIRD;
    }

    public function calculateResult(VotingData $votingData): int
    {
        if ($votingData->votesYes >= (2 * $votingData->votesNo)) { // same as: $yes >= (($yes + $no) * 2/3)
            return IMotion::STATUS_ACCEPTED;
        } else {
            return IMotion::STATUS_REJECTED;
        }
    }
}
