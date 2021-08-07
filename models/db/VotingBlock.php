<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $consultationId
 * @property string $title
 * @property int|null $majorityType
 * @property int|null $votesPublic
 * @property string|null $usersPresentByOrga
 * @property string|null $activityLog
 * @property int $votingStatus
 *
 * @property Consultation $consultation
 * @property Amendment[] $amendments
 * @property Motion[] $motions
 * @property Vote[] $votes
 */
class VotingBlock extends ActiveRecord
{
    // HINT: keep in sync with admin-votings.vue.php

    // The voting is not performed using Antragsgrün
    const STATUS_OFFLINE = 0;

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    const STATUS_PREPARING = 1;

    // Currently open for voting. Currently there should only be one voting in this status at a time.
    const STATUS_OPEN = 2;

    // Vorting is closed.
    const STATUS_CLOSED = 3;

    // More yes- than no-votes
    const MAJORITY_TYPE_SIMPLE = 1;

    const ACTIVITY_TYPE_OPENED = 1;
    const ACTIVITY_TYPE_CLOSED = 2;
    const ACTIVITY_TYPE_CANCEL = 3;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'votingBlock';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getMyConsultation(): Consultation {
        $current = Consultation::getCurrent();
        if ($current && $current->id === $this->consultationId) {
            return $current;
        } else {
            return Consultation::findOne($this->consultationId);
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendments()
    {
        return $this->hasMany(Amendment::class, ['votingBlockId' => 'id'])
            ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::class, ['votingBlockId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotes()
    {
        return $this->hasMany(Vote::class, ['votingBlockId' => 'id']);
    }

    public function getUserVote(User $user, string $itemType, int $itemId): ?Vote
    {
        foreach ($this->votes as $vote) {
            if ($vote->userId === $user->id && $itemType === 'motion' && $vote->motionId === $itemId) {
                return $vote;
            }
            if ($vote->userId === $user->id && $itemType === 'amendment' && $vote->amendmentId === $itemId) {
                return $vote;
            }
        }
        return null;
    }

    /**
     * @return Vote[]
     */
    public function getVotesForMotion(Motion $motion): array
    {
        return array_values(array_filter($this->votes, function (Vote $vote) use ($motion): bool {
            return $vote->motionId === $motion->id;
        }));
    }

    /**
     * @return Vote[]
     */
    public function getVotesForAmendment(Amendment $amendment): array
    {
        return array_values(array_filter($this->votes, function (Vote $vote) use ($amendment): bool {
            return $vote->amendmentId === $amendment->id;
        }));
    }

    public function userIsAllowedToVoteFor(User $user, string $itemType, int $itemId): bool
    {
        if ($this->getUserVote($user, $itemType, $itemId)) {
            return false;
        }
        if ($this->votingStatus !== static::STATUS_OPEN) {
            return false;
        }
        // Now we assume every user may vote
        if ($itemType === 'motion') {
            foreach ($this->motions as $motion) {
                if ($motion->id === $itemId) {
                    return true;
                }
            }
        }
        if ($itemType === 'amendment') {
            foreach ($this->amendments as $amendment) {
                if ($amendment->id === $itemId) {
                    return true;
                }
            }
        }
        return false;
    }

    public function switchToOfflineVoting(): void {
        if ($this->votingStatus === VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_CANCEL);
        }
        $this->votingStatus = VotingBlock::STATUS_OFFLINE;
        $this->save();
    }

    public function switchToOnlineVoting(): void {
        if ($this->votingStatus === VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_CANCEL);
        }
        $this->votingStatus = VotingBlock::STATUS_PREPARING;
        $this->save();
    }

    public function openVoting(): void {
        if ($this->votingStatus !== VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_OPENED);
        }
        $this->votingStatus = VotingBlock::STATUS_OPEN;
        $this->save();
    }

    public function closeVoting(): void {
        if ($this->votingStatus !== VotingBlock::STATUS_CLOSED) {
            $this->addActivity(static::ACTIVITY_TYPE_CANCEL);
        }
        $this->votingStatus = VotingBlock::STATUS_CLOSED;
        $this->save();
    }

    public function getActivityLog(): array
    {
        if (!$this->activityLog) {
            return [];
        }
        return json_decode($this->activityLog, true);
    }

    protected function addActivity(int $type): void
    {
        $activityLog = $this->getActivityLog();
        $activityLog[] = [
            'type' => $type,
            'date' => date('c'),
        ];
        $this->activityLog = json_encode($activityLog);
    }

    public function getActivityLogForApi(): array
    {
        return array_map(function (array $activity): array {
            return [
                'type' => $activity['type'],
                'date' => $activity['date'],
            ];
        }, $this->getActivityLog());
    }

    public function getUsersPresentByOrganizations(): array {
        if (!$this->usersPresentByOrga) {
            return [];
        }
        return json_decode($this->usersPresentByOrga, true);
    }

    public function setUserPresentByOrganization(string $organization, ?int $users): void
    {
        $present = $this->getUsersPresentByOrganizations();
        if ($users !== null) {
            $present[$organization] = $users;
        } elseif (isset($present[$organization])) {
            unset($present[$organization]);
        }
        $this->usersPresentByOrga = json_encode($present);
    }

    public function getUserPresentByOrganization(string $organization): ?int
    {
        $present = $this->getUsersPresentByOrganizations();

        return $present[$organization] ?? null;
    }
}
