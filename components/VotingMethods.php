<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, ConsultationUserGroup, IMotion, IVotingItem, Motion, User, Vote, VotingBlock, VotingQuestion};
use app\models\api\voting\{VotingBlockUser, VotingPayloadBuilder, VotingStatus};
use app\models\exceptions\FormError;
use app\models\majorityType\IMajorityType;
use app\models\proposedProcedure\Factory;
use app\models\quorumType\IQuorumType;
use app\models\policies\{IPolicy, UserGroups};
use app\models\settings\{VotingBlock as VotingBlockSettings};
use app\models\votings\AnswerTemplates;
use yii\web\Request;

/**
 * Methods used by VotingController, making it easier to access them from unit tests
 */
class VotingMethods
{
    private Consultation $consultation;
    private Request $request;

    public function setRequestData(Consultation $consultation, Request $request): void
    {
        $this->consultation = $consultation;
        $this->request = $request;
    }

    /**
     * The status is named as the payload spells it (VotingStatus), not as the database stores it.
     *
     * @throws FormError
     */
    public function voteStatusUpdate(VotingBlock $votingBlock): void
    {
        if ($this->request->post('status') === null) {
            return;
        }

        $newStatus = VotingStatus::tryFrom((string)$this->request->post('status'));
        if ($newStatus === null) {
            throw new FormError('Unknown voting status: ' . $this->request->post('status'));
        }

        match ($newStatus) {
            VotingStatus::PREPARING => $votingBlock->switchToOnlineVoting(),
            VotingStatus::OPEN => $votingBlock->openVoting(),
            VotingStatus::CLOSED_PUBLISHED => $votingBlock->closeVoting(publish: true),
            VotingStatus::CLOSED_UNPUBLISHED => $votingBlock->closeVoting(publish: false),
            VotingStatus::OFFLINE => $votingBlock->switchToOfflineVoting(),
        };
    }

    public function deleteVoting(VotingBlock $votingBlock): void
    {
        $votingBlock->deleteVoting();
    }

    public function getPolicyFromUpdateData(VotingBlock $votingBlock, int $policyId, ?array $userGroups): IPolicy
    {
        $submittedUserGroups = array_map('intval', $userGroups ?? []);

        $consultation = $votingBlock->getMyConsultation();
        $policy = IPolicy::getInstanceFromDb((string)$policyId, $consultation, $votingBlock);
        if (is_a($policy, UserGroups::class)) {
            $policy->setAllowedUserGroups(ConsultationUserGroup::loadGroupsByIdForConsultation($votingBlock->getMyConsultation(), $submittedUserGroups));
        }
        return $policy;
    }

    public function voteSaveSettings(VotingBlock $votingBlock): void
    {
        $settings = $votingBlock->getSettings();

        if ($this->request->post('title')) {
            $votingBlock->setTitle($this->request->post('title', ''));
        }
        if ($this->request->post('assignedMotion') !== null && $this->request->post('assignedMotion') > 0) {
            $votingBlock->assignedToMotionId = $this->request->post('assignedMotion');
        } else {
            $votingBlock->assignedToMotionId = null;
        }
        if ($this->request->post('votingTime') !== null && $this->request->post('votingTime') > 0) {
            $settings->votingTime = intval($this->request->post('votingTime'));
        } else {
            $settings->votingTime = null;
        }
        if ($this->request->post('resultsPublic') !== null) {
            $votingBlock->resultsPublic = intval($this->request->post('resultsPublic'));
        } else {
            $votingBlock->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
        }
        if ($this->request->post('votesNames') !== null) {
            $settings->votesNames = intval($this->request->post('votesNames'));
        } else {
            $settings->votesNames = VotingBlockSettings::VOTES_NAMES_AUTH;
        }
        if (in_array($votingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
            if ($this->request->post('maxVotesByGroup') !== null && $this->request->post('maxVotesByGroup') !== '') {
                $settings->maxVotesByGroup = array_map(fn(array $setting): array => [
                    'maxVotes' => intval($setting['maxVotes']),
                    'groupId' => isset($setting['groupId']) && $setting['groupId'] !== '' ? intval($setting['groupId']) : null,
                ], $this->request->post('maxVotesByGroup'));
            } else {
                $settings->maxVotesByGroup = null;
            }
            if ($this->request->post('answerTemplate') !== null) {
                $votingBlock->setAnswerTemplate(intval($this->request->post('answerTemplate')));
            } else {
                $votingBlock->setAnswerTemplate(AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION);
            }
            if ($this->request->post('votePolicy') !== null) {
                $policyData = $this->request->post('votePolicy', []);
                // A policy that admits nobody in particular sends no groups at all; a form-encoded
                // request turns that into an empty string rather than into an absent value
                $userGroups = $policyData['user_groups'] ?? null;
                $votingBlock->setVotingPolicy($this->getPolicyFromUpdateData(
                    $votingBlock,
                    intval($policyData['id']),
                    is_array($userGroups) ? $userGroups : []
                ));
            }
            if ($this->request->post('votesPublic') !== null) {
                $votingBlock->votesPublic = intval($this->request->post('votesPublic'));
            } else {
                $votingBlock->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
            }
            if ($this->request->post('majorityType') !== null) {
                $votingBlock->majorityType = intval($this->request->post('majorityType'));
            } else {
                $votingBlock->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
            }
            if ($this->request->post('quorumType') !== null && is_a($votingBlock->getVotingPolicy(), UserGroups::class)) {
                $votingBlock->quorumType = intval($this->request->post('quorumType', IQuorumType::QUORUM_TYPE_NONE));
            } else {
                $votingBlock->quorumType = IQuorumType::QUORUM_TYPE_NONE;
            }
        }
        $votingBlock->setSettings($settings);
        $votingBlock->save();

        if (in_array($votingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
            $existingAbstention = $votingBlock->getGeneralAbstentionItem();
            if ($this->request->post('hasGeneralAbstention', false)) {
                if (!$existingAbstention) {
                    VotingQuestion::createGeneralAbstentionItem($votingBlock);
                }
            } else {
                $existingAbstention?->delete();
            }
        }
    }

    public function voteAddIMotion(VotingBlock $votingBlock): void
    {
        if ($votingBlock->votingStatus !== VotingBlock::STATUS_PREPARING) {
            throw new FormError('Not possible to remove items in this state');
        }
        /** @var IMotion[] $items */
        $items = [];
        $idParts = explode('-', $this->request->post('itemDefinition', ''));

        if (count($idParts) === 2 && $idParts[0] === 'motion' && $idParts[1] > 0) {
            $items[] = $this->consultation->getMotion($idParts[1]);
        } elseif (count($idParts) === 2 && $idParts[0] === 'amendment' && $idParts[1] > 0) {
            $items[] = $this->consultation->getAmendment(intval($idParts[1]));
        } elseif (count($idParts) === 3 && $idParts[0] === 'motion' && $idParts[1] > 0 && $idParts[2] === 'amendments') {
            $motion = $this->consultation->getMotion($idParts[1]);
            $filter = IMotionStatusFilter::onlyUserVisible($this->consultation, false)->noAmendmentsIfMotionIsMoved();
            foreach ($motion->getFilteredAndSortedAmendments($filter) as $amendment) {
                $items[] = $amendment;
            }
        }

        foreach ($items as $item) {
            if ($item->votingBlockId === null) {
                $item->addToVotingBlock($votingBlock, true);
            }
        }
    }

    public function voteAddQuestion(VotingBlock $votingBlock): void
    {
        if ($votingBlock->votingStatus !== VotingBlock::STATUS_PREPARING) {
            throw new FormError('Not possible to remove items in this state');
        }

        $question = new VotingQuestion();
        $question->title = $this->request->post('question', '-');
        $question->consultationId = $votingBlock->consultationId;
        $question->votingBlockId = $votingBlock->id;
        $question->save();
    }

    public function voteRemoveItem(VotingBlock $votingBlock): void
    {
        if ($votingBlock->votingStatus !== VotingBlock::STATUS_PREPARING) {
            throw new FormError('Not possible to remove items in this state');
        }
        /** @var IVotingItem|null $item */
        $item = null;
        $itemId = intval($this->request->post('itemId'));
        if ($this->request->post('itemType') === 'motion') {
            $item = $this->consultation->getMotion($itemId);
        }
        if ($this->request->post('itemType') === 'amendment') {
            $item = $this->consultation->getAmendment($itemId);
        }
        if ($this->request->post('itemType') === 'question') {
            $item = $votingBlock->getQuestionById($itemId);
        }
        if (!$item) {
            throw new FormError('Item not found');
        }
        if ($item->getVotingData()->itemGroupSameVote) {
            foreach ($votingBlock->getItemGroupItems($item->getVotingData()->itemGroupSameVote) as $item) {
                $item->removeFromVotingBlock($votingBlock, true);
            }
        } else {
            if ($item->getVotingBlockId() === $votingBlock->id) {
                $item->removeFromVotingBlock($votingBlock, true);
            }
        }
    }

    /**
     * @throws FormError
     */
    private function getVotingItemByTypeAndId(string $itemType, int $itemId, VotingBlock $votingBlock): IVotingItem
    {
        $item = null;
        if ($itemType === 'amendment') {
            $item = $this->consultation->getAmendment($itemId);
        }
        if ($itemType === 'motion') {
            $item = $this->consultation->getMotion($itemId);
        }
        if ($itemType === 'question') {
            $item = $this->consultation->getVotingQuestion($itemId);
        }

        if (!$item) {
            throw new FormError('Item not found');
        }
        if ($item->votingBlockId !== $votingBlock->id) {
            throw new FormError('Item not part of this voting block');
        }

        return $item;
    }

    /**
     * @throws FormError
     */
    private function voteForSingleItem(User $user, VotingBlock $votingBlock, IVotingItem $item, string $voteChoice): Vote {
        $vote = $votingBlock->getUserSingleItemVote($user, $item);
        if (!$votingBlock->userIsCurrentlyAllowedToVoteFor($user, $item, $vote)) {
            throw new FormError('Not possible to vote for this item');
        }

        $vote = new Vote();
        $vote->userId = $user->id;
        $vote->votingBlockId = $votingBlock->id;
        $vote->setVoteFromApi($voteChoice, $votingBlock->getAnswers());
        $vote->motionId = (is_a($item, Motion::class) ? $item->id : null);
        $vote->amendmentId = (is_a($item, Amendment::class) ? $item->id : null);
        $vote->questionId = (is_a($item, VotingQuestion::class) ? $item->id : null);
        $vote->weight = $user->getSettingsObj()->getVoteWeight($votingBlock->getMyConsultation());

        // A vote keeps the publicity the voting promised when it was opened, even if the setting is
        // widened afterwards. Decided by the voting alone: what the client believed is irrelevant.
        $vote->public = $votingBlock->getPublicityForNewVotes();

        $vote->dateVote = date('Y-m-d H:i:s');

        return $vote;
    }

    private function undoVoteForSingleItem(User $user, VotingBlock $votingBlock, IVotingItem $item): void {
        $exitingVote = $votingBlock->getUserSingleItemVote($user, $item);
        if (!$exitingVote) {
            throw new FormError('Vote not found');
        }
        $exitingVote->delete();
    }

    /**
     * @throws FormError
     */
    private function voteForItemGroup(User $user, VotingBlock $votingBlock, string $itemGroup, string $voteChoice): void {
        $votes = [];
        foreach ($votingBlock->getItemGroupItems($itemGroup) as $imotion) {
            $votes[] = $this->voteForSingleItem($user, $votingBlock, $imotion, $voteChoice);
        }
        foreach ($votes as $vote) {
            $vote->save();
        }
    }

    private function undoVoteForItemGroup(User $user, VotingBlock $votingBlock, string $itemGroup): void {
        foreach ($votingBlock->getItemGroupItems($itemGroup) as $item) {
            try {
                $exitingVote = $votingBlock->getUserSingleItemVote($user, $item);
                $exitingVote->delete();
            } catch (FormError $e) {
                // To make eventual inconsistencies at least not worse, let's remove all further votes anyway
            }
        }
    }

    public function userSetAbstention(VotingBlock $votingBlock, User $user): void
    {
        $votes = $votingBlock->getVotesForUser($user);
        if (count($votes) > 0) {
            throw new FormError('Already voted - not possible to abstain anymore');
        }
        $abstentionItem = $votingBlock->getGeneralAbstentionItem();
        if (!$abstentionItem) {
            throw new FormError('Abstaining is not possible');
        }

        $hasAbstained = $votingBlock->userHasAbstained($user);

        ResourceLock::lockVotingItemForVoting($abstentionItem);
        try {
            $abstentionData = $this->request->post('abstention');
            if ($abstentionData['abstain']) {
                if (!$hasAbstained) {
                    $vote = $this->voteForSingleItem($user, $votingBlock, $abstentionItem, 'yes');
                    $vote->save();
                }
            } else {
                if ($hasAbstained) {
                    $this->undoVoteForSingleItem($user, $votingBlock, $abstentionItem);
                }
            }
            ResourceLock::unlockVotingItemForVoting($abstentionItem);
        } catch (FormError $e) {
            ResourceLock::unlockVotingItemForVoting($abstentionItem);
            throw $e;
        }
    }

    /**
     * Item groups holding a single item are named after that item; every other group ID is one that
     * was configured for voting several items on together.
     *
     * @throws FormError
     */
    private function getSingleItemFromGroupId(string $groupId, VotingBlock $votingBlock): ?IVotingItem
    {
        if (!str_starts_with($groupId, VotingPayloadBuilder::SINGLE_ITEM_GROUP_PREFIX)) {
            return null;
        }

        $parts = explode(':', substr($groupId, strlen(VotingPayloadBuilder::SINGLE_ITEM_GROUP_PREFIX)));
        if (count($parts) !== 2 || !in_array($parts[0], ['motion', 'amendment', 'question'], true)) {
            throw new FormError('Invalid vote');
        }

        return $this->getVotingItemByTypeAndId($parts[0], intval($parts[1]), $votingBlock);
    }

    /**
     * @throws FormError
     */
    public function userVote(VotingBlock $votingBlock, User $user): void
    {
        if ($votingBlock->userHasAbstained($user)) {
            throw new FormError('Voting is not possible after abstaining');
        }

        foreach ($this->request->post('votes', []) as $voteData) {
            $groupId = trim((string)($voteData['groupId'] ?? ''));
            if ($groupId === '') {
                throw new FormError('Invalid vote');
            }
            $singleItem = $this->getSingleItemFromGroupId($groupId, $votingBlock);

            if ($singleItem === null) {
                ResourceLock::lockVotingBlockItemGroup($votingBlock, $groupId);
                try {
                    if ($voteData['vote'] === 'undo') {
                        $this->undoVoteForItemGroup($user, $votingBlock, $groupId);
                    } else {
                        $this->voteForItemGroup($user, $votingBlock, $groupId, $voteData['vote']);
                    }
                    ResourceLock::unlockVotingBlockItemGroup($votingBlock, $groupId);
                } catch (FormError $e) {
                    ResourceLock::unlockVotingBlockItemGroup($votingBlock, $groupId);
                    throw $e;
                }
            } else {
                $item = $singleItem;
                ResourceLock::lockVotingItemForVoting($item);
                try {
                    if ($voteData['vote'] === 'undo') {
                        $this->undoVoteForSingleItem($user, $votingBlock, $item);
                    } else {
                        $vote = $this->voteForSingleItem($user, $votingBlock, $item, $voteData['vote']);
                        $vote->save();
                    }
                    ResourceLock::unlockVotingItemForVoting($item);
                } catch (FormError $e) {
                    ResourceLock::unlockVotingItemForVoting($item);
                    throw $e;
                }
            }
        }
    }

    /**
     * @return VotingBlockUser[]
     */
    public function getOpenVotingsForUser(bool $showAllOpen, ?Motion $assignedToMotion, User $user): array
    {
        $votingData = [];
        foreach (Factory::getOpenVotingBlocks($this->consultation, $showAllOpen, $assignedToMotion) as $voting) {
            $votingData[] = $voting->getUserApiObject($user);
        }
        return $votingData;
    }

    /**
     * @return VotingBlockUser[]
     */
    public function getClosedPublishedVotingsForUser(User $user): array
    {
        $votingData = [];
        foreach (Factory::getPublishedClosedVotingBlocks($this->consultation) as $voting) {
            $votingData[] = $voting->getUserApiObject($user);
        }
        return $votingData;
    }

    /**
     * @param int[] $votingIds
     * @return VotingBlock[] the votings that actually moved - the ones a live event has to be sent
     *                       about, since the order is part of their payload
     */
    public function sortVotings(array $votingIds): array
    {
        $positionById = [];
        for ($pos = 0; $pos < count($votingIds); $pos++) {
            $positionById[$votingIds[$pos]] = count($votingIds) - $pos - 1;
        }
        $firstUnusedPos = $pos;

        $moved = [];
        foreach ($this->consultation->votingBlocks as $votingBlock) {
            $oldPosition = $votingBlock->position;
            if (isset($positionById[$votingBlock->id])) {
                $votingBlock->position = $positionById[$votingBlock->id];
            } else {
                $votingBlock->position = $firstUnusedPos;
                $firstUnusedPos++;
            }
            if ($votingBlock->position !== $oldPosition) {
                $moved[] = $votingBlock;
            }
            $votingBlock->save();
        }

        return $moved;
    }
}
