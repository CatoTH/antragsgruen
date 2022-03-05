<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\settings\VotingData;
use app\models\db\{IMotion, Vote};

class HalfQuorum extends IQuorumType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'quorum_half');
    }

    public static function getDescription(): string
    {
        return \Yii::t('voting', 'quorum_half_h');
    }

    public static function getID(): int
    {
        return IQuorumType::QUORUM_TYPE_HALF;
    }

    public function hasReachedQuorum(VotingData $votingData): bool
    {
        // @TODO
        return false;
    }
}
