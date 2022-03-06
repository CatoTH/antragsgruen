<?php

declare(strict_types=1);

namespace app\models\quorumType;

use app\models\db\VotingBlock;

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


    public function getQuorum(VotingBlock $votingBlock): int
    {
        return 0;
    }
}
