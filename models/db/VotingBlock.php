<?php

namespace app\models\db;

use app\components\UrlHelper;
use app\models\exceptions\Internal;
use app\models\majorityType\IMajorityType;
use app\models\policies\{IPolicy, LoggedIn};
use app\models\quorumType\{IQuorumType, NoQuorum};
use app\models\settings\{AntragsgruenApp, VotingBlock as VotingBlockSettings};
use app\models\votings\{Answer, AnswerTemplates, VotingItemGroup};
use app\models\settings\VotingData;
use yii\db\{ActiveQuery, ActiveRecord, Query};

/**
 * @property int $id
 * @property int $consultationId
 * @property int $position
 * @property int $type
 * @property string $title
 * @property int|null $majorityType
 * @property int|null $quorumType
 * @property int|null $votesPublic
 * @property int|null $resultsPublic
 * @property int|null $assignedToMotionId
 * @property string|null $usersPresentByOrga
 * @property string|null $answers
 * @property string|null $policyVote
 * @property string|null $activityLog
 * @property int $votingStatus
 * @property string|null $settings
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
    public const STATUS_OFFLINE = 0;

    // Votings that have been created and will be using Antragsgrün, but are not active yet
    public const STATUS_PREPARING = 1;

    // Open for voting.
    public const STATUS_OPEN = 2;

    // Voting is closed, the results are visible for users.
    public const STATUS_CLOSED_PUBLISHED = 3;

    // Voting is closed, the results are not visible for users.
    public const STATUS_CLOSED_UNPUBLISHED = 4;

    // Voting is deleted - not accessible in the frontend.
    public const STATUS_DELETED = -1;

    // Nobody can see who voted how
    public const VOTES_PUBLIC_NO = 0;

    // Admins can see who voted how
    public const VOTES_PUBLIC_ADMIN = 1;

    // Everyone with voting rights can see who voted how
    public const VOTES_PUBLIC_ALL = 2;

    // No detailed voting results are visible
    public const RESULTS_PUBLIC_NO = 0;

    // Detailed voting results (number of yes/no votes) are visible
    public const RESULTS_PUBLIC_YES = 1;

    public const ACTIVITY_TYPE_OPENED = 1;
    public const ACTIVITY_TYPE_CLOSED = 2;
    public const ACTIVITY_TYPE_RESET = 3;
    public const ACTIVITY_TYPE_REOPENED = 4;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'votingBlock';
    }

    /**
     * @return ActiveQuery<Consultation>
     */
    public function getConsultation(): ActiveQuery
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

    public function setTitle(string $title): void
    {
        if (grapheme_strlen($title) > 150) {
            $this->title = grapheme_substr($title, 0, 147) . '...';
        } else {
            $this->title = $title;
        }
    }

    /**
     * @return ActiveQuery<Amendment>
     */
    public function getAmendments(): ActiveQuery
    {
        return $this->hasMany(Amendment::class, ['votingBlockId' => 'id'])
            ->andWhere(Amendment::tableName() . '.status != ' . Amendment::STATUS_DELETED);
    }

    /**
     * @return ActiveQuery<Motion>
     */
    public function getMotions(): ActiveQuery
    {
        return $this->hasMany(Motion::class, ['votingBlockId' => 'id'])
            ->andWhere(Motion::tableName() . '.status != ' . Motion::STATUS_DELETED);
    }

    /**
     * @return ActiveQuery<VotingQuestion>
     */
    public function getQuestions(): ActiveQuery
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

    /**
     * @return ActiveQuery<Motion>
     */
    public function getAssignedToMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'assignedToMotionId']);
    }

    /**
     * @return ActiveQuery<Vote>
     */
    public function getVotes(): ActiveQuery
    {
        return $this->hasMany(Vote::class, ['votingBlockId' => 'id']);
    }

    private ?VotingBlockSettings $settingsObject = null;

    public function getSettings(): VotingBlockSettings
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new VotingBlockSettings($this->settings);
        }

        return $this->settingsObject;
    }

    public function setSettings(?VotingBlockSettings $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function getUserLink(): string
    {
        if ($this->isClosed()) {
            return UrlHelper::createUrl('consultation/voting-results');
        } else {
            if ($this->assignedToMotionId && $this->assignedToMotion) {
                return UrlHelper::createMotionUrl($this->assignedToMotion);
            } else {
                return UrlHelper::homeUrl();
            }
        }
    }

    /** @var array<int, Vote[]> */
    private array $votesByUserCache = [];

    /** Set once every vote of this voting has been loaded, so that people without one are known too */
    private bool $allVotesOfUsersLoaded = false;

    /**
     * Both caches hold votes as they were when they were first asked for, so reloading the voting
     * has to drop them - which is what the callers that cast a vote and then build a payload from
     * the same object rely on.
     */
    public function refresh(): bool
    {
        $this->votesByUserCache = [];
        $this->allVotesOfUsersLoaded = false;
        $this->votesSortedByItemCache = null;

        return parent::refresh();
    }

    /**
     * Everything one person voted for in this voting, the general abstention included.
     *
     * Deliberately not filtered out of the votes of everyone: a payload built for a participant of a
     * secret voting needs nothing but this, and loading the votes of two thousand delegates to find
     * the two of one of them is what made every poll expensive.
     *
     * @return Vote[]
     */
    public function getAllVotesOfUser(User $user): array
    {
        if (!isset($this->votesByUserCache[$user->id])) {
            if ($this->allVotesOfUsersLoaded) {
                return [];
            }
            $this->votesByUserCache[$user->id] = Vote::find()
                ->where(['votingBlockId' => $this->id, 'userId' => $user->id])
                ->all();
        }

        return $this->votesByUserCache[$user->id];
    }

    /**
     * Loads the votes of everyone at once, for the one caller that needs the state of many people:
     * the live event, which describes the voting for every person it is delivered to. Asking per
     * person would be one query each.
     */
    public function preloadVotesOfAllUsers(): void
    {
        if ($this->allVotesOfUsersLoaded) {
            return;
        }

        $this->votesByUserCache = [];
        foreach (Vote::find()->where(['votingBlockId' => $this->id])->all() as $vote) {
            $this->votesByUserCache[$vote->userId][] = $vote;
        }
        $this->allVotesOfUsersLoaded = true;
    }

    /**
     * @return int[] the IDs of everyone who has cast a vote here
     */
    public function getVoterUserIds(): array
    {
        $ids = (new Query())
            ->select('userId')
            ->distinct()
            ->from(Vote::tableName())
            ->where(['votingBlockId' => $this->id])
            ->andWhere(['not', ['userId' => null]])
            ->column();

        return array_map('intval', $ids);
    }

    public function getUserSingleItemVote(User $user, IVotingItem $item): ?Vote
    {
        foreach ($this->getAllVotesOfUser($user) as $vote) {
            if ($vote->isForVotingItem($item)) {
                return $vote;
            }
        }
        return null;
    }

    /** @var null|Vote[][] */
    private ?array $votesSortedByItemCache = null;

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

    /**
     * @return Vote[]
     */
    public function getVotesForQuestion(VotingQuestion $question): array
    {
        $this->initVotesSortedCache();
        if (isset($this->votesSortedByItemCache['question.' . $question->id])) {
            return $this->votesSortedByItemCache['question.' . $question->id];
        } else {
            return [];
        }
    }

    /**
     * @return Vote[]
     */
    public function getVotesForUser(User $user): array
    {
        $abstentionId = $this->getGeneralAbstentionItem()?->id;

        return array_values(array_filter(
            $this->getAllVotesOfUser($user),
            fn (Vote $vote): bool => $vote->questionId === null || $vote->questionId !== $abstentionId
        ));
    }

    public function userHasAbstained(User $user): bool
    {
        $abstentionId = $this->getGeneralAbstentionItem()?->id;
        foreach ($this->getAllVotesOfUser($user) as $vote) {
            if ($vote->questionId !== null && $vote->questionId === $abstentionId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Vote[]
     */
    public function getVotesForVotingItem(IVotingItem $votingItem): array
    {
        if (is_a($votingItem, Amendment::class)) {
            return $this->getVotesForAmendment($votingItem);
        } elseif (is_a($votingItem, Motion::class)) {
            return $this->getVotesForMotion($votingItem);
        } else {
            /** @var VotingQuestion $votingItem */
            return $this->getVotesForQuestion($votingItem);
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

    public function getQuorumType(): IQuorumType
    {
        if ($this->quorumType === null) {
            return new NoQuorum();
        }
        $quorumTypes = IQuorumType::getQuorumTypes();
        if (!isset($quorumTypes[$this->quorumType])) {
            throw new Internal('Unsupported quorum type: ' . $this->quorumType);
        }
        /** @var IQuorumType $quorumType */
        $quorumType = new $quorumTypes[$this->quorumType]();
        return $quorumType;
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

    /**
     * If there are vote limits set for this voting block, this returns the maximum number of votes applicable
     * for the given user based on their user group memberships.
     * If the user has multiple user groups eligible to vote here, then the one with the most votes is taken.
     * The user group with ID==null (default) applies to all user.
     * If there are vote limits set, but the user is not member of any eligible group and no default group is set, 0 votes are allows.
     *
     * @return int|null - null means no restriction
     */
    private function getMaxVotesForUser(User $user): ?int
    {
        $maxVotesSettings = $this->getSettings()->maxVotesByGroup;
        if ($maxVotesSettings === null) {
            return null;
        }
        $userGroupIds = array_map(fn(ConsultationUserGroup $group) => $group->id, $user->getConsultationUserGroups($this->getMyConsultation()));
        $maxVotes = 0;
        foreach ($maxVotesSettings as $setting) {
            if ($setting['groupId'] === null || in_array($setting['groupId'], $userGroupIds)) {
                if ($setting['maxVotes'] > $maxVotes) {
                    $maxVotes = $setting['maxVotes'];
                }
            }
        }

        return $maxVotes;
    }

    public function getUserRemainingVotes(User $user): ?int
    {
        $maxVotes = $this->getMaxVotesForUser($user);
        if ($maxVotes === null) {
            return null;
        }
        return $maxVotes - count($this->getVotesForUser($user));
    }

    public function userIsCurrentlyAllowedToVoteFor(User $user, IVotingItem $item, ?Vote $vote): bool
    {
        if ($vote) {
            // The user has already voted
            return false;
        }
        if ($this->votingStatus !== static::STATUS_OPEN) {
            return false;
        }

        $remainingVotes = $this->getUserRemainingVotes($user);
        if ($remainingVotes !== null && $remainingVotes <= 0) {
            return false;
        }

        return $this->userIsGenerallyAllowedToVoteFor($user, $item);
    }

    private function clearVotesPublicSnapshot(): void
    {
        $settings = $this->getSettings();
        $settings->votesPublicAtOpening = null;
        $this->setSettings($settings);
    }

    /**
     * How public a vote cast right now may become: never more than the voting promised when it was
     * opened, and never more than it says now. Deliberately not influenced by anything the client
     * sends - a voter cannot be given a stricter or a wider publicity than everyone else.
     */
    public function getPublicityForNewVotes(): int
    {
        $current = $this->votesPublic ?? self::VOTES_PUBLIC_NO;
        $promised = $this->getSettings()->votesPublicAtOpening;

        return $promised === null ? $current : min($promised, $current);
    }

    private function resetItemResults(): void
    {
        foreach ($this->motions as $motion) {
            $motion->votingStatus = IMotion::STATUS_VOTE;
            $motion->save();
        }
        foreach ($this->amendments as $amendment) {
            $amendment->votingStatus = IMotion::STATUS_VOTE;
            $amendment->save();
        }
        foreach ($this->questions as $question) {
            $question->votingStatus = IMotion::STATUS_VOTE;
            $question->save();
        }
    }

    public function switchToOfflineVoting(): void {
        if ($this->votingStatus === VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_RESET);
        }
        $this->votingStatus = VotingBlock::STATUS_OFFLINE;
        $this->clearVotesPublicSnapshot();
        $this->save();
    }

    public function switchToOnlineVoting(): void {
        if ($this->votingStatus === VotingBlock::STATUS_OPEN || $this->isClosed()) {
            $this->addActivity(static::ACTIVITY_TYPE_RESET);
            $this->resetItemResults();
        }
        $this->votingStatus = VotingBlock::STATUS_PREPARING;
        $this->clearVotesPublicSnapshot();
        $this->save();

        foreach ($this->votes as $vote) {
            $vote->delete();
        }
    }

    public function openVoting(): void {
        if ($this->isClosed()) {
            $this->addActivity(static::ACTIVITY_TYPE_REOPENED);
        } elseif ($this->votingStatus !== VotingBlock::STATUS_OPEN) {
            $this->addActivity(static::ACTIVITY_TYPE_OPENED);
        }
        if ($this->majorityType === null) {
            $this->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
        }
        if ($this->quorumType === null) {
            $this->quorumType = IQuorumType::QUORUM_TYPE_NONE;
        }
        if ($this->votesPublic === null) {
            $this->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
        }
        if ($this->resultsPublic === null) {
            $this->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
        }
        $this->votingStatus = VotingBlock::STATUS_OPEN;

        $settings = $this->getSettings();
        $settings->openedTs = time();
        // Only if there is none yet: re-opening a closed voting continues the same round, whose
        // votes are still there and were cast under the publicity of the first opening
        $settings->votesPublicAtOpening ??= $this->votesPublic;
        $this->setSettings($settings);

        $this->save();

        ConsultationLog::log($this->getMyConsultation(), User::getCurrentUser()->id, ConsultationLog::VOTING_OPEN, $this->id);
    }

    private function closeVoting_setResultToItem(IVotingItem $item, VotingData $votingData): void
    {
        $item->setVotingData($votingData);
        if ($votingData->quorumReached === false) {
            $item->setVotingResult(IMotion::STATUS_QUORUM_MISSED);
        } elseif ($this->votingHasMajority()) {
            $result = $this->getMajorityType()->calculateResult($votingData);
            $item->setVotingResult($result);
        } elseif ($votingData->quorumReached === true) {
            $item->setVotingResult(IMotion::STATUS_QUORUM_REACHED);
        }
        $item->save();
    }

    public function closeVoting(bool $publish): void {
        if (!$this->isClosed()) {
            $this->addActivity(static::ACTIVITY_TYPE_CLOSED);
        }

        // The results are written to the items before the voting is marked as closed: a request
        // arriving in between would otherwise see a voting that is over but has no results yet
        foreach ($this->motions as $motion) {
            $votingData = $motion->getVotingData()->augmentWithResults($this, $motion);
            $this->closeVoting_setResultToItem($motion, $votingData);
        }
        foreach ($this->amendments as $amendment) {
            $votingData = $amendment->getVotingData()->augmentWithResults($this, $amendment);
            $this->closeVoting_setResultToItem($amendment, $votingData);
        }
        foreach ($this->questions as $question) {
            $votingData = $question->getVotingData()->augmentWithResults($this, $question);
            $this->closeVoting_setResultToItem($question, $votingData);
        }

        $this->votingStatus = ($publish ? VotingBlock::STATUS_CLOSED_PUBLISHED : VotingBlock::STATUS_CLOSED_UNPUBLISHED);
        $this->save();

        ConsultationLog::log($this->getMyConsultation(), User::getCurrentUser()->id, ConsultationLog::VOTING_CLOSE, $this->id);
    }

    public function deleteVoting(): void
    {
        $this->votingStatus = static::STATUS_DELETED;
        $this->save();

        foreach ($this->motions as $motion) {
            $motion->votingBlockId = null;
            $motion->save();
        }
        foreach ($this->amendments as $amendment) {
            $amendment->votingBlockId = null;
            $amendment->save();
        }
        foreach ($this->questions as $question) {
            $question->votingBlockId = null;
            $question->save();
        }

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
        $this->activityLog = (string)json_encode($activityLog);
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
        return in_array($this->votingStatus, [
            VotingBlock::STATUS_OFFLINE,
            VotingBlock::STATUS_PREPARING,
            VotingBlock::STATUS_CLOSED_PUBLISHED,
            VotingBlock::STATUS_CLOSED_UNPUBLISHED
        ]);
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

    /**
     * @return array{votes: int, users: int, abstentions: int}
     */
    public function getVoteStatistics(): array
    {
        $total = 0;
        $voteUserIds = [];
        $abstainedUserIds = [];

        // The items of this voting, not those of the whole consultation: the statistics are asked for
        // on every poll and on every cast vote, and loading every motion of a consultation with a few
        // hundred of them to find the handful in this voting is what made that expensive
        $groupsMyMotionIds = [];
        $groupsMyAmendmentIds = [];
        $groupsMyQuestionsIds = [];
        foreach ($this->motions as $motion) {
            if ($motion->getVotingData()->itemGroupSameVote) {
                $groupsMyMotionIds[$motion->id] = $motion->getVotingData()->itemGroupSameVote;
            }
        }
        foreach ($this->amendments as $amendment) {
            // An amendment whose motion has been deleted is not part of the consultation any more,
            // and was not counted here before either (AgendaVoting skips it for the same reason)
            if (!$amendment->getMyMotion()) {
                continue;
            }
            if ($amendment->getVotingData()->itemGroupSameVote) {
                $groupsMyAmendmentIds[$amendment->id] = $amendment->getVotingData()->itemGroupSameVote;
            }
        }
        foreach ($this->questions as $question) {
            $groupsMyQuestionsIds[$question->id] = $question->getVotingData()->itemGroupSameVote;
        }

        $abstentionId = $this->getGeneralAbstentionItem()?->id;

        // Which item a vote belongs to is all this needs; loading the votes as objects only to read
        // four of their columns is what made the turnout expensive to ask for
        $votes = (new Query())
            ->select(['userId', 'motionId', 'amendmentId', 'questionId'])
            ->from(Vote::tableName())
            ->where(['votingBlockId' => $this->id])
            ->all();

        // If three motions are in a voting group, there will be three votes in the database.
        // For the statistics, we should only count them once.
        $countedItemGroups = [];
        foreach ($votes as $vote) {
            $userId = ($vote['userId'] !== null ? intval($vote['userId']) : null);
            $motionId = ($vote['motionId'] !== null ? intval($vote['motionId']) : null);
            $amendmentId = ($vote['amendmentId'] !== null ? intval($vote['amendmentId']) : null);
            $questionId = ($vote['questionId'] !== null ? intval($vote['questionId']) : null);

            if ($questionId !== null && $questionId === $abstentionId) {
                $abstainedUserIds[] = $userId;
                if ($userId && !in_array($userId, $voteUserIds)) {
                    $voteUserIds[] = $userId;
                }
            }

            $groupId = null;
            if ($motionId !== null && isset($groupsMyMotionIds[$motionId])) {
                $groupId = $groupsMyMotionIds[$motionId];
            }
            if ($amendmentId !== null && isset($groupsMyAmendmentIds[$amendmentId])) {
                $groupId = $groupsMyAmendmentIds[$amendmentId];
            }
            if ($questionId !== null && isset($groupsMyQuestionsIds[$questionId])) {
                $groupId = $groupsMyQuestionsIds[$questionId];
            }

            if ($groupId && in_array($groupId, $countedItemGroups)) {
                continue;
            }

            $total++;
            if ($userId && !in_array($userId, $voteUserIds)) {
                $voteUserIds[] = $userId;
            }

            if ($groupId) {
                $countedItemGroups[] = $groupId;
            }
        }

        return ['votes' => $total, 'users' => count($voteUserIds), 'abstentions' => count($abstainedUserIds)];
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

    public function getGeneralAbstentionItem(): ?VotingQuestion
    {
        foreach ($this->questions as $question) {
            if ($question->isGeneralAbstention()) {
                return $question;
            }
        }
        return null;
    }

    /**
     * @return VotingBlock[]
     */
    public static function getPublishedClosedVotings(Consultation $consultation): array
    {
        return array_values(array_filter($consultation->votingBlocks, function (VotingBlock $votingBlock) {
            return $votingBlock->votingStatus === VotingBlock::STATUS_CLOSED_PUBLISHED;
        }));
    }

    public static function getNextAvailablePosition(Consultation $consultation): int
    {
        $position = 0;
        foreach ($consultation->votingBlocks as $votingBlock) {
            if ($votingBlock->position >= $position) {
                $position = $votingBlock->position + 1;
            }
        }

        return $position;
    }

    public function isClosed(): bool
    {
        return $this->votingStatus === VotingBlock::STATUS_CLOSED_UNPUBLISHED ||
            $this->votingStatus === VotingBlock::STATUS_CLOSED_PUBLISHED;
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
