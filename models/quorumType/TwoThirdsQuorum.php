<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\db\VotingBlock;

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

    public function getQuorum(VotingBlock $votingBlock): int
    {
        $userCount = $this->getRelevantEligibleVotersCount($votingBlock);

        return (int)ceil($userCount * 2 / 3); // 42 users => quorum is 28. 43 users => quorum is 29.
    }
}
