<?php

namespace app\models\db;

use app\models\exceptions\FormError;
use app\models\settings\{AntragsgruenApp, VotingData};
use app\models\votings\Answer;
use yii\db\{ActiveQuery, ActiveRecord, Query};

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

    /**
     * @return ActiveQuery<VotingBlock>
     */
    public function getVotingBlock(): ActiveQuery
    {
        return $this->hasOne(VotingBlock::class, ['id' => 'votingBlockId'])
            ->andWhere(VotingBlock::tableName() . '.votingStatus != ' . VotingBlock::STATUS_DELETED);
    }

    /**
     * @return ActiveQuery<Motion>
     */
    public function getMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    /**
     * @return ActiveQuery<Amendment>
     */
    public function getAmendment(): ActiveQuery
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    /**
     * @return ActiveQuery<VotingQuestion>
     */
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
     * How one item of a voting was voted on, by answer.
     *
     * Counted by the database: this is asked for on every poll and after every cast vote, once per
     * item group, and loading every vote of the voting as an object to add up its weight was the
     * most expensive thing about a payload nobody may see the single votes in anyway.
     *
     * @return array<int|string, array<string, int>> organization (default: "0") => answer => weight
     */
    public static function calculateVoteResultsForApi(VotingBlock $voting, IVotingItem $item): array
    {
        foreach (AntragsgruenApp::getActivePlugins() as $pluginClass) {
            $results = $pluginClass::calculateVoteResultsForApi($voting, $item);
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

        $weightByVote = self::sumWeightsByAnswer($voting, $item);
        foreach ($answers as $answer) {
            if (isset($weightByVote[$answer->dbId])) {
                $results[VotingData::ORGANIZATION_DEFAULT][$answer->apiId] = $weightByVote[$answer->dbId];
            }
        }

        return $results;
    }

    /**
     * @return array<int, int> the answer as it is stored => the summed weight of the votes for it
     */
    private static function sumWeightsByAnswer(VotingBlock $voting, IVotingItem $item): array
    {
        $column = match (true) {
            is_a($item, Motion::class) => 'motionId',
            is_a($item, Amendment::class) => 'amendmentId',
            default => 'questionId',
        };

        $rows = (new Query())
            ->select(['vote', 'weight' => 'SUM(weight)'])
            ->from(self::tableName())
            ->where(['votingBlockId' => $voting->id, $column => $item->getId()])
            ->groupBy('vote')
            ->all();

        $weights = [];
        foreach ($rows as $row) {
            $weights[intval($row['vote'])] = intval($row['weight']);
        }

        return $weights;
    }
}
