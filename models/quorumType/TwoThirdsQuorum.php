<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\settings\VotingData;
use app\models\db\{IMotion, Vote};

class TwoThirdsQuorum extends IQuorumType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'quorum_two_third');
    }

    public static function getDescription(): string
    {
        return \Yii::t('voting', 'quorum_two_third_h');
    }

    public static function getID(): int
    {
        return IQuorumType::QUORUM_TYPE_TWO_THIRD;
    }

    public function hasReachedQuorum(VotingData $votingData): bool
    {
        // @TODO
        return true;
    }
}
