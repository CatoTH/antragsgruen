<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $userId
 * @property int $votingBlockId
 * @property int|null $motionId
 * @property int|null $amendmentId
 * @property int $vote
 * @property int $public
 * @property string $dateVote
 *
 * @property User $user
 * @property VotingBlock $votingBlock
 * @property Amendment|null $amendment
 * @property Motion|null $motion
 */
class Vote extends ActiveRecord
{
    const VOTE_ABSTENTION = 0;
    const VOTE_YES = 1;
    const VOTE_NO = -1;

    /**
     * @return string
     */
    public static function tableName()
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'vote';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotingBlock()
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    public function getVoteForApi(): ?string
    {
        switch ($this->vote) {
            case Vote::VOTE_YES:
                return 'yes';
            case Vote::VOTE_NO:
                return 'no';
            case Vote::VOTE_ABSTENTION:
                return 'abstention';
            default:
                return null;
        }
    }

    public function setVoteFromApi(string $vote): void
    {
        switch ($vote) {
            case 'yes':
                $this->vote = self::VOTE_YES;
                break;
            case 'no':
                $this->vote = self::VOTE_NO;
                break;
            case 'abstention':
                $this->vote = self::VOTE_ABSTENTION;
                break;
            default:
                $this->vote = null;
        }
    }
}
