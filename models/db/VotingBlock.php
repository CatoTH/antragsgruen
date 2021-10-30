<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\majorityType\IMajorityType;
use app\models\settings\AntragsgruenApp;
use app\models\VotingItemGroup;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $consultationId
 * @property string $title
 * @property int|null $majorityType
 * @property int|null $votesPublic
 * @property int|null $resultsPublic
 * @property int|null $assignedToMotionId
 * @property string|null $usersPresentByOrga
 * @property string|null $activityLog
 * @property int $votingStatus
 *
 * @property Consultation $consultation
 * @property Amendment[] $amendments
 * @property Motion[] $motions
 * @property Vote[] $votes
 * @property Motion|null $assignedToMotion
 */
class VotingBlock extends ActiveRecord
{
    // HINT: keep in sync with admin-votings.vue.php & voting-block.vue.php

    // The voting is not performed using Antragsgrün
    const STATUS_OFFLINE = 0;

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    const STATUS_PREPARING = 1;

    // Currently open for voting. Currently there should only be one voting in this status at a time.
    const STATUS_OPEN = 2;

    // Vorting is closed.
    const STATUS_CLOSED = 3;

    // Vorting is deleted - not accessible in the frontend.
    const STATUS_DELETED = -1;

    // Nobody can see who voted how
    const VOTES_PUBLIC_NO = 0;

    // Admins can see who voted how
    const VOTES_PUBLIC_ADMIN = 1;

    // Everyone with voting rights can see who voted how
    const VOTES_PUBLIC_ALL = 2;

    // No detailed voting results are visible
    const RESULTS_PUBLIC_NO = 0;

    // Detailed voting results (number of yes/no votes) are visible
    const RESULTS_PUBLIC_YES = 1;

    const ACTIVITY_TYPE_OPENED = 1;
    const ACTIVITY_TYPE_CLOSED = 2;
    const ACTIVITY_TYPE_RESET = 3;
    const ACTIVITY_TYPE_REOPENED = 4;

    /**
     * @return string
     */
    public static function tableName()
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'votingBlock';
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

    public function getAssignedToMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'assignedToMotionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVotes()
    {
        return $this->hasMany(Vote::class, ['votingBlockId' => 'id']);
    }

    public function getUserSingleItemVote(User $user, IMotion $imotion): ?Vote
    {
        foreach ($this->votes as $vote) {
            if ($vote->userId === $user->id && is_a($imotion, Motion::class) && $vote->motionId === $imotion->id) {
                return $vote;
            }
            if ($vote->userId === $user->id && is_a($imotion, Amendment::class) && $vote->amendmentId === $imotion->id) {
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

    public function getMajorityType(): IMajorityType
    {
        $majorityTypes = IMajorityType::getMajorityTypes();
        if (!isset($majorityTypes[$this->majorityType])) {
            throw new Internal('Unsupported majority type: ' . $this->majorityType);
        }
        return new $majorityTypes[$this->majorityType]();
    }

    public function userIsGenerallyAllowedToVoteFor(User $user, IMotion $imotion): bool
    {
        $foundImotion = false;
        if (is_a($imotion, Motion::class)) {
            foreach ($this->motions as $motion) {
                if ($motion->id === $imotion->id) {
                    $foundImotion = true;
                }
            }
        }
        if (is_a($imotion, Amendment::class)) {
            foreach ($this->amendments as $amendment) {
                if ($amendment->id === $imotion->id) {
                    $foundImotion = true;
                }
            }
        }
        if (!$foundImotion) {
            return false;
        }

        // In case a plugin provides eligibility check, we take its result. The first plugin providing the check wins.
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $allowed = $plugin::userIsAllowedToVoteFor($this, $user, $imotion);
            if ($allowed !== null) {
                return $allowed;
            }
        }

        // If no plugin
        return true;
    }

    public function userIsCurrentlyAllowedToVoteFor(User $user, IMotion $imotion): bool
    {
        if ($this->getUserSingleItemVote($user, $imotion)) {
            // The user has already voted
            return false;
        }
        if ($this->votingStatus !== static::STATUS_OPEN) {
            return false;
        }

        return $this->userIsGenerallyAllowedToVoteFor($user, $imotion);
    }

    public function switchToOfflineVoting(): void {
        if ($this->votingStatus === VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_RESET);
        }
        $this->votingStatus = VotingBlock::STATUS_OFFLINE;
        $this->save();
    }

    public function switchToOnlineVoting(): void {
        if ($this->votingStatus === VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_RESET);
        }
        $this->votingStatus = VotingBlock::STATUS_PREPARING;
        $this->save();

        foreach ($this->votes as $vote) {
            $vote->delete();
        }
    }

    public function openVoting(): void {
        if ($this->votingStatus === VotingBlock::STATUS_CLOSED) {
            $this->addActivity(static::ACTIVITY_TYPE_REOPENED);
        } elseif ($this->votingStatus !== VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_OPENED);
        }
        if ($this->majorityType === null) {
            $this->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
        }
        if ($this->votesPublic === null) {
            $this->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
        }
        if ($this->resultsPublic === null) {
            $this->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
        }
        $this->votingStatus = VotingBlock::STATUS_OPEN;
        $this->save();

        ConsultationLog::log($this->getMyConsultation(), User::getCurrentUser()->id, ConsultationLog::VOTING_OPEN, $this->id);
    }

    public function closeVoting(): void {
        if ($this->votingStatus !== VotingBlock::STATUS_CLOSED) {
            $this->addActivity(static::ACTIVITY_TYPE_RESET);
        }
        $this->votingStatus = VotingBlock::STATUS_CLOSED;
        $this->save();

        foreach ($this->motions as $motion) {
            $votes = $this->getVotesForMotion($motion);
            $result = $this->getMajorityType()->calculateResult($votes);
            $votingData = $motion->getVotingData()->augmentWithResults($this, $votes);
            $motion->setVotingData($votingData);
            $motion->setVotingResult($result);
            $motion->save();
        }

        foreach ($this->amendments as $amendment) {
            $votes = $this->getVotesForAmendment($amendment);
            $result = $this->getMajorityType()->calculateResult($votes);
            $votingData = $amendment->getVotingData()->augmentWithResults($this, $votes);
            $amendment->setVotingData($votingData);
            $amendment->setVotingResult($result);
            $amendment->save();
        }

        ConsultationLog::log($this->getMyConsultation(), User::getCurrentUser()->id, ConsultationLog::VOTING_CLOSE, $this->id);
    }

    public function deleteVoting(): void
    {
        $this->votingStatus = static::STATUS_DELETED;
        $this->save();

        ConsultationLog::log($this->getMyConsultation(), User::getCurrentUser()->id, ConsultationLog::VOTING_DELETE, $this->id);
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

    public function itemsCanBeAdded(): bool
    {
        return in_array($this->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING]);
    }

    public function itemsCanBeRemoved(): bool
    {
        return in_array($this->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING, VotingBlock::STATUS_CLOSED]);
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

    /**
     * @return VotingItemGroup[]
     */
    public function getVotingItemBlocks(bool $includeUngrouped, ?IMotion $adhocFilter): array
    {
        $groups = [];
        $ungrouped = [];

        foreach ($this->getMyConsultation()->motions as $motion) {
            if ($motion->votingBlockId === $this->id && $motion->getVotingData()->itemGroupSameVote) {
                if (!isset($groups[$motion->getVotingData()->itemGroupSameVote])) {
                    $groups[$motion->getVotingData()->itemGroupSameVote] = new VotingItemGroup($motion->getVotingData()->itemGroupSameVote, null);
                }
                $groups[$motion->getVotingData()->itemGroupSameVote]->motions[] = $motion;
                $groups[$motion->getVotingData()->itemGroupSameVote]->motionIds[] = $motion->id;
            }
            if ($motion->votingBlockId === $this->id && $motion->getVotingData()->itemGroupSameVote === null && $includeUngrouped) {
                $ungrouped[] = new VotingItemGroup(null, $motion);
            }

            foreach ($motion->amendments as $amendment) {
                if ($amendment->votingBlockId === $this->id && $amendment->getVotingData()->itemGroupSameVote) {
                    if (!isset($groups[$amendment->getVotingData()->itemGroupSameVote])) {
                        $groups[$amendment->getVotingData()->itemGroupSameVote] = new VotingItemGroup($amendment->getVotingData()->itemGroupSameVote, null);
                    }
                    $groups[$amendment->getVotingData()->itemGroupSameVote]->amendments[] = $amendment;
                    $groups[$amendment->getVotingData()->itemGroupSameVote]->amendmentIds[] = $amendment->id;
                }
                if ($amendment->votingBlockId === $this->id && $amendment->getVotingData()->itemGroupSameVote === null && $includeUngrouped) {
                    $ungrouped[] = new VotingItemGroup(null, $amendment);
                }
            }
        }
        $groups = array_merge($groups, $ungrouped);
        if ($adhocFilter) {
            $groups = array_filter($groups, function (VotingItemGroup $group) use ($adhocFilter): bool {
                return !$group->isOnlyMyselfGroup($adhocFilter);
            });
        }
        return array_values($groups);
    }

    public function getVoteStatistics(): array
    {
        $total = 0;
        $voteUserIds = [];

        $groupsMyMotionIds = [];
        $groupsMyAmendmentIds = [];
        foreach ($this->getMyConsultation()->motions as $motion) {
            if ($motion->votingBlockId === $this->id && $motion->getVotingData()->itemGroupSameVote) {
                $groupsMyMotionIds[$motion->id] = $motion->getVotingData()->itemGroupSameVote;
            }
            foreach ($motion->amendments as $amendment) {
                if ($amendment->votingBlockId === $this->id && $amendment->getVotingData()->itemGroupSameVote) {
                    $groupsMyAmendmentIds[$amendment->id] = $amendment->getVotingData()->itemGroupSameVote;
                }
            }
        }

        // If three motions are in a voting group, there will be three votes in the database.
        // For the statistics, we should only count them once.
        $countedItemGroups = [];
        foreach ($this->votes as $vote) {
            $groupId = null;
            if ($vote->motionId !== null && isset($groupsMyMotionIds[$vote->motionId])) {
                $groupId = $groupsMyMotionIds[$vote->motionId];
            }
            if ($vote->amendmentId !== null && isset($groupsMyAmendmentIds[$vote->amendmentId])) {
                $groupId = $groupsMyAmendmentIds[$vote->amendmentId];
            }

            if ($groupId && in_array($groupId, $countedItemGroups)) {
                continue;
            }

            $total++;
            if ($vote->userId && !in_array($vote->userId, $voteUserIds)) {
                $voteUserIds[] = $vote->userId;
            }

            if ($groupId) {
                $countedItemGroups[] = $groupId;
            }
        }

        return [$total, count($voteUserIds)];
    }

    /**
     * @return IMotion[]
     */
    public function getItemGroupItems(?string $itemGroupId): array
    {
        $items = [];
        foreach ($this->getMyConsultation()->motions as $motion) {
            if ($itemGroupId === $motion->getVotingData()->itemGroupSameVote && $this->id === $motion->votingBlockId) {
                $items[] = $motion;
            }

            foreach ($motion->amendments as $amendment) {
                if ($itemGroupId === $amendment->getVotingData()->itemGroupSameVote && $this->id === $amendment->votingBlockId) {
                    $items[] = $amendment;
                }
            }
        }
        return $items;
    }

    /**
     * @return VotingBlock[]
     */
    public static function getClosedVotings(Consultation $consultation): array
    {
        return array_values(array_filter($consultation->votingBlocks, function (VotingBlock $votingBlock) {
            return $votingBlock->votingStatus === static::STATUS_CLOSED;
        }));
    }
}
