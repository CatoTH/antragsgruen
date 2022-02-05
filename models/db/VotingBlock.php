<?php

namespace app\models\db;

use app\models\exceptions\Internal;
use app\models\majorityType\IMajorityType;
use app\models\policies\IPolicy;
use app\models\policies\LoggedIn;
use app\models\settings\AntragsgruenApp;
use app\models\votings\{Answer, AnswerTemplates, VotingItemGroup};
use app\models\settings\VotingData;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $consultationId
 * @property int $type
 * @property string $title
 * @property int|null $majorityType
 * @property int|null $votesPublic
 * @property int|null $resultsPublic
 * @property int|null $assignedToMotionId
 * @property string|null $usersPresentByOrga
 * @property string|null $answers
 * @property string|null $policyVote
 * @property string|null $activityLog
 * @property int $votingStatus
 *
 * @property Consultation $consultation
 * @property Amendment[] $amendments
 * @property Motion[] $motions
 * @property VotingQuestion[] $questions
 * @property Vote[] $votes
 * @property Motion|null $assignedToMotion
 */
class VotingBlock extends ActiveRecord implements IHasPolicies
{
    // HINT: keep in sync with admin-votings.vue.php & voting-block.vue.php

    // The voting is not performed using Antragsgrün
    const STATUS_OFFLINE = 0;

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    const STATUS_PREPARING = 1;

    // Open for voting.
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

    public static function tableName(): string
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getQuestions()
    {
        return $this->hasMany(VotingQuestion::class, ['votingBlockId' => 'id']);
    }

    public function getQuestionById(int $questionId): ?VotingQuestion
    {
        foreach ($this->questions as $question) {
            if ($question->id === $questionId) {
                return $question;
            }
        }
        return null;
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

    public function getUserSingleItemVote(User $user, IVotingItem $item): ?Vote
    {
        foreach ($this->votes as $vote) {
            if ($vote->userId === $user->id && is_a($item, Motion::class) && $vote->motionId === $item->id) {
                return $vote;
            }
            if ($vote->userId === $user->id && is_a($item, Amendment::class) && $vote->amendmentId === $item->id) {
                return $vote;
            }
            if ($vote->userId === $user->id && is_a($item, VotingQuestion::class) && $vote->questionId === $item->id) {
                return $vote;
            }
        }
        return null;
    }

    /** @var null|Vote[][] */
    private $votesSortedByItemCache = null;

    private function initVotesSortedCache(): void
    {
        if ($this->votesSortedByItemCache !== null) {
            return;
        }
        foreach ($this->votes as $vote) {
            if ($vote->motionId > 0) {
                $key = 'motion.' . $vote->motionId;
            } elseif ($vote->amendmentId > 0) {
                $key = 'amendment.' . $vote->amendmentId;
            } elseif ($vote->questionId > 0) {
                $key = 'question.' . $vote->questionId;
            } else {
                continue;
            }
            if (!isset($this->votesSortedByItemCache[$key])) {
                $this->votesSortedByItemCache[$key] = [];
            }
            $this->votesSortedByItemCache[$key][] = $vote;
        }
    }

    /**
     * @return Vote[]
     */
    public function getVotesForMotion(Motion $motion): array
    {
        $this->initVotesSortedCache();
        if (isset($this->votesSortedByItemCache['motion.' . $motion->id])) {
            return $this->votesSortedByItemCache['motion.' . $motion->id];
        } else {
            return [];
        }
    }

    /**
     * @return Vote[]
     */
    public function getVotesForAmendment(Amendment $amendment): array
    {
        $this->initVotesSortedCache();
        if (isset($this->votesSortedByItemCache['amendment.' . $amendment->id])) {
            return $this->votesSortedByItemCache['amendment.' . $amendment->id];
        } else {
            return [];
        }
    }

    public function getVotesForQuestion(VotingQuestion $question): array
    {
        $this->initVotesSortedCache();
        if (isset($this->votesSortedByItemCache['question.' . $question->id])) {
            return $this->votesSortedByItemCache['question.' . $question->id];
        } else {
            return [];
        }
    }

    public function getMajorityType(): IMajorityType
    {
        $majorityTypes = IMajorityType::getMajorityTypes();
        if (!isset($majorityTypes[$this->majorityType])) {
            throw new Internal('Unsupported majority type: ' . $this->majorityType);
        }
        return new $majorityTypes[$this->majorityType]();
    }

    public function userIsGenerallyAllowedToVoteFor(User $user, IVotingItem $item): bool
    {
        $foundItem = false;
        if (is_a($item, Motion::class)) {
            foreach ($this->motions as $motion) {
                if ($motion->id === $item->id) {
                    $foundItem = true;
                }
            }
        }
        if (is_a($item, Amendment::class)) {
            foreach ($this->amendments as $amendment) {
                if ($amendment->id === $item->id) {
                    $foundItem = true;
                }
            }
        }
        if (is_a($item, VotingQuestion::class)) {
            foreach ($this->questions as $question) {
                if ($question->id === $item->id) {
                    $foundItem = true;
                }
            }
        }
        if (!$foundItem) {
            return false;
        }

        return $this->getVotingPolicy()->checkUser($user, false, false);
    }

    public function userIsCurrentlyAllowedToVoteFor(User $user, IVotingItem $item): bool
    {
        if ($this->getUserSingleItemVote($user, $item)) {
            // The user has already voted
            return false;
        }
        if ($this->votingStatus !== static::STATUS_OPEN) {
            return false;
        }

        return $this->userIsGenerallyAllowedToVoteFor($user, $item);
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

    private function closeVoting_setResultToItem(IVotingItem $item, VotingData $votingData): void
    {
        $item->setVotingData($votingData);
        if ($this->votingHasMajority()) {
            $result = $this->getMajorityType()->calculateResult($votingData);
            $item->setVotingResult($result);
        }
        $item->save();
    }

    public function closeVoting(): void {
        if ($this->votingStatus !== VotingBlock::STATUS_CLOSED) {
            $this->addActivity(static::ACTIVITY_TYPE_RESET);
        }
        $this->votingStatus = VotingBlock::STATUS_CLOSED;
        $this->save();

        foreach ($this->motions as $motion) {
            $votes = $this->getVotesForMotion($motion);
            $votingData = $motion->getVotingData()->augmentWithResults($this, $votes);
            $this->closeVoting_setResultToItem($motion, $votingData);
        }
        foreach ($this->amendments as $amendment) {
            $votes = $this->getVotesForAmendment($amendment);
            $votingData = $amendment->getVotingData()->augmentWithResults($this, $votes);
            $this->closeVoting_setResultToItem($amendment, $votingData);
        }
        foreach ($this->questions as $question) {
            $votes = $this->getVotesForQuestion($question);
            $votingData = $question->getVotingData()->augmentWithResults($this, $votes);
            $this->closeVoting_setResultToItem($question, $votingData);
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

    /**
     * @return Answer[]
     */
    public function getAnswers(): array
    {
        return AnswerTemplates::fromVotingBlockData($this->getAnswerTemplate());
    }

    public function votingHasMajority(): bool
    {
        return $this->getAnswerTemplate() === AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION ||
               $this->getAnswerTemplate() === AnswerTemplates::TEMPLATE_YES_NO;
    }

    public function getAnswerTemplate(): int
    {
        if (empty($this->answers)) {
            return AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION;
        }
        $spec = json_decode($this->answers, true);
        return $spec['template'] ?? AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION;
    }

    public function setAnswerTemplate(int $templateId): void
    {
        $obj = ($this->answers ? json_decode($this->answers, true) : []);
        $obj['template'] = $templateId;
        $this->answers = (string)json_encode($obj);
    }

    public function getVotingPolicy(): IPolicy
    {
        $policy = ($this->policyVote === null || $this->policyVote === '' ? (string)LoggedIn::getPolicyID() : $this->policyVote);
        return IPolicy::getInstanceFromDb($policy, $this->getMyConsultation(), $this);
    }

    public function setVotingPolicy(IPolicy $policy): void
    {
        $this->policyVote = $policy->serializeInstanceForDb();
    }

    /**
     * @return VotingItemGroup[]
     */
    public function getVotingItemBlocks(bool $includeUngrouped, ?IMotion $adhocFilter): array
    {
        $groups = [];
        $ungrouped = [];

        foreach ($this->getMyConsultation()->motions as $motion) {
            $votingData = $motion->getVotingData();
            if ($motion->votingBlockId === $this->id && $votingData->itemGroupSameVote) {
                if (!isset($groups[$votingData->itemGroupSameVote])) {
                    $groups[$votingData->itemGroupSameVote] = new VotingItemGroup($votingData->itemGroupSameVote, $votingData->itemGroupName, null);
                }
                $groups[$votingData->itemGroupSameVote]->motions[] = $motion;
                $groups[$votingData->itemGroupSameVote]->motionIds[] = $motion->id;
            }
            if ($motion->votingBlockId === $this->id && $votingData->itemGroupSameVote === null && $includeUngrouped) {
                $ungrouped[] = new VotingItemGroup(null, null, $motion);
            }

            foreach ($motion->amendments as $amendment) {
                $votingData = $amendment->getVotingData();
                if ($amendment->votingBlockId === $this->id && $votingData->itemGroupSameVote) {
                    if (!isset($groups[$votingData->itemGroupSameVote])) {
                        $groups[$votingData->itemGroupSameVote] = new VotingItemGroup($votingData->itemGroupSameVote, $votingData->itemGroupName, null);
                    }
                    $groups[$votingData->itemGroupSameVote]->amendments[] = $amendment;
                    $groups[$votingData->itemGroupSameVote]->amendmentIds[] = $amendment->id;
                }
                if ($amendment->votingBlockId === $this->id && $amendment->getVotingData()->itemGroupSameVote === null && $includeUngrouped) {
                    $ungrouped[] = new VotingItemGroup(null, null, $amendment);
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
        $groupsMyQuestionsIds = [];
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
        foreach ($this->getMyConsultation()->votingQuestions as $question) {
            $groupsMyQuestionsIds[$question->id] = $question->getVotingData()->itemGroupSameVote;
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
            if ($vote->questionId !== null && isset($groupsMyQuestionsIds[$vote->questionId])) {
                $groupId = $groupsMyQuestionsIds[$vote->questionId];
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
     * @return IVotingItem[]
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
        foreach ($this->questions as $question) {
            if ($itemGroupId === $question->getVotingData()->itemGroupSameVote && $this->id === $question->votingBlockId) {
                    $items[] = $question;
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

    public function getAdminSetupHintHtml(): ?string
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $hint = $plugin::getVotingAdminSetupHintHtml($this);
            if ($hint) {
                return $hint;
            }
        }
        return null;
    }

    // Hint: deadlines for votings are not implemented yet
    public function isInDeadline(string $type): bool
    {
        return true;
    }

    public function getDeadlinesByType(string $type): array
    {
        return [];
    }
}
