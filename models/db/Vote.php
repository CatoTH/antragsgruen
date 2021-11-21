<?php

namespace app\models\db;

use app\models\exceptions\FormError;
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

    public function getUser(): User
    {
        return User::getCachedUser($this->userId);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotingBlock()
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
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
                throw new FormError('Invalid vote: ' . $vote);
        }
    }

    public function isForIMotion(IMotion $IMotion): bool
    {
        if (is_a($IMotion, Amendment::class)) {
            return $this->amendmentId === $IMotion->id;
        } else {
            return $this->motionId === $IMotion->id;
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
}
