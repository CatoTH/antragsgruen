<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\db\VotingBlock;

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

    public function getQuorum(VotingBlock $votingBlock): int
    {
        $userCount = $this->getRelevantEligibleVotersCount($votingBlock);

        return (int)ceil($userCount / 2); // 42 users => quorum is 21. 43 users => quorum is 22.
    }
}
