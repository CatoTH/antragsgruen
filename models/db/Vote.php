<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\settings\AntragsgruenApp;
use app\models\settings\VotingData;
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

    const VOTE_API_ABSTENTION = 'abstention';
    const VOTE_API_YES = 'yes';
    const VOTE_API_NO = 'no';

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
            case static::VOTE_YES:
                return static::VOTE_API_YES;
            case static::VOTE_NO:
                return static::VOTE_API_NO;
            case static::VOTE_ABSTENTION:
                return static::VOTE_API_ABSTENTION;
            default:
                return null;
        }
    }

    public function setVoteFromApi(string $vote): void
    {
        switch ($vote) {
            case static::VOTE_API_YES:
                $this->vote = self::VOTE_YES;
                break;
            case static::VOTE_API_NO:
                $this->vote = self::VOTE_NO;
                break;
            case static::VOTE_API_ABSTENTION:
                $this->vote = self::VOTE_ABSTENTION;
                break;
            default:
                $this->vote = null;
        }
    }

    /**
     * @param Vote[] $votes
     */
    public static function calculateVoteResultsForApi(VotingBlock $voting, array $votes): array
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $results = $pluginClass::calculateVoteResultsForApi($voting, $votes);
            if ($results) {
                return $results;
            }
        }

        $results = [
            User::ORGANIZATION_DEFAULT => [
                static::VOTE_API_YES => 0,
                static::VOTE_API_NO => 0,
                static::VOTE_API_ABSTENTION => 0,
            ],
        ];
        foreach ($votes as $vote) {
            $voteType = $vote->getVoteForApi();
            $results[User::ORGANIZATION_DEFAULT][$voteType]++;
        }
        return $results;
    }

    /**
     * @param Vote[] $votes
     */
    public static function calculateFinalVoteResult(VotingBlock $voting, array $votes): int
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $results = $pluginClass::calculateFinalVoteResult($voting, $votes);
            if ($results) {
                return $results;
            }
        }

        if ($voting->majorityType !== VotingBlock::MAJORITY_TYPE_SIMPLE) {
            throw new Internal('Unsupported majority type: ' . $voting->majorityType);
        }
        $yes = 0;
        $no = 0;
        foreach ($votes as $vote) {
            if ($vote->vote === Vote::VOTE_YES) {
                $yes++;
            }
            if ($vote->vote === Vote::VOTE_NO) {
                $no++;
            }
        }

        if ($yes > $no) {
            return IMotion::STATUS_ACCEPTED;
        } else {
            return IMotion::STATUS_REJECTED;
        }
    }
}
