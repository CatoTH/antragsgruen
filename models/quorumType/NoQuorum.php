<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\settings\VotingData;
use app\models\db\{IMotion, Vote};

class NoQuorum extends IQuorumType
{
    public static function getName(): string
    {
        return \Yii::t('voting', 'quorum_none');
    }

    public static function getDescription(): string
    {
        return '';
    }

    public static function getID(): int
    {
        return IQuorumType::QUORUM_TYPE_NONE;
    }

    public function hasReachedQuorum(VotingData $votingData): bool
    {
        return true;
    }
}
