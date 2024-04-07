<?php

namespace app\models\db;

use app\models\exceptions\FormError;
use app\models\settings\AntragsgruenApp;
use app\models\settings\VotingData;
use app\models\votings\Answer;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $userId
 * @property int $votingBlockId
 * @property int|null $motionId
 * @property int|null $amendmentId
 * @property int|null $questionId
 * @property int $vote
 * @property int $weight
 * @property int $public
 * @property string $dateVote
 *
 * @property VotingBlock $votingBlock
 * @property Amendment|null $amendment
 * @property Motion|null $motion
 * @property VotingQuestion|null $question
 */
class Vote extends ActiveRecord
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'vote';
    }

    public function getUser(): ?User
    {
        return User::getCachedUser($this->userId);
    }

    public function getVotingBlock(): ActiveQuery
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    public function getMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    public function getAmendment(): ActiveQuery
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    public function getQuestion(): ActiveQuery
    {
        return $this->hasOne(VotingQuestion::class, ['id' => 'questionId']);
    }

    /**
     * @param Answer[] $answers
     */
    public function getVoteForApi(array $answers): ?string
    {
        foreach ($answers as $answer) {
            if ($answer->dbId === $this->vote) {
                return $answer->apiId;
            }
        }
        return null;
    }

    /**
     * @param Answer[] $answers
     * @throws FormError
     */
    public function setVoteFromApi(string $vote, array $answers): void
    {
        foreach ($answers as $answer) {
            if ($answer->apiId === $vote) {
                $this->vote = $answer->dbId;
                return;
            }
        }
        throw new FormError('Invalid vote: ' . $vote);
    }

    public function isForVotingItem(IVotingItem $item): bool
    {
        if (is_a($item, Amendment::class)) {
            return $this->amendmentId === $item->id;
        } elseif (is_a($item, Motion::class)) {
            return $this->motionId === $item->id;
        } else {
            /** @var VotingQuestion $item */
            return $this->questionId === $item->id;
        }
    }

    /**
     * @param Vote[] $votes
     *
     * @return array<int|string, array<string, int>>
     */
    public static function calculateVoteResultsForApi(VotingBlock $voting, array $votes): array
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $results = $pluginClass::calculateVoteResultsForApi($voting, $votes);
            if ($results) {
                return $results;
            }
        }

        $answers = $voting->getAnswers();
        $results = [
            VotingData::ORGANIZATION_DEFAULT => [],
        ];
        foreach ($answers as $answer) {
            $results[VotingData::ORGANIZATION_DEFAULT][$answer->apiId] = 0;
        }
        foreach ($votes as $vote) {
            $voteType = $vote->getVoteForApi($answers);
            $results[VotingData::ORGANIZATION_DEFAULT][$voteType] += $vote->weight;
        }
        return $results;
    }
}
