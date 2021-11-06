<?php

declare(strict_types=1);

namespace app\models\majorityType;

use app\models\settings\VotingData;
use app\models\db\{IMotion, Vote};

class SimpleMajority extends IMajorityType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'majority_simple');
    }

    public static function getDescription(): string
    {
        return \Yii::t('voting', 'majority_simple_h');
    }

    public static function getID(): int
    {
        return IMajorityType::MAJORITY_TYPE_SIMPLE;
    }

    public function calculateResult(VotingData $votingData): int
    {
        if ($votingData->votesYes > $votingData->votesNo) {
            return IMotion::STATUS_ACCEPTED;
        } else {
            return IMotion::STATUS_REJECTED;
        }
    }
}
