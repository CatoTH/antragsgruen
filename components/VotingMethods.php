<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, Consultation, ConsultationUserGroup, IMotion, IVotingItem, Motion, User, Vote, VotingBlock, VotingQuestion};
use app\models\exceptions\FormError;
use app\models\majorityType\IMajorityType;
use app\models\proposedProcedure\Factory;
use app\models\quorumType\IQuorumType;
use app\models\policies\{IPolicy, UserGroups};
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

    public function voteStatusUpdate(VotingBlock $votingBlock): void
    {
        if ($this->request->post('status') !== null) {
            $newStatus = intval($this->request->post('status'));
            if ($newStatus === VotingBlock::STATUS_PREPARING) {
                $votingBlock->switchToOnlineVoting();
            } elseif ($newStatus === VotingBlock::STATUS_OPEN) {
                $votingBlock->openVoting();
            } elseif ($newStatus === VotingBlock::STATUS_CLOSED_PUBLISHED) {
                $votingBlock->closeVoting(true);
            } elseif ($newStatus === VotingBlock::STATUS_CLOSED_UNPUBLISHED) {
                $votingBlock->closeVoting(false);
            } elseif ($newStatus === VotingBlock::STATUS_OFFLINE) {
                $votingBlock->switchToOfflineVoting();
            }
        }
    }

    public function deleteVoting(VotingBlock $votingBlock)
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
        if (in_array($votingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
            if ($this->request->post('answerTemplate') !== null) {
                $votingBlock->setAnswerTemplate(intval($this->request->post('answerTemplate')));
            } else {
                $votingBlock->setAnswerTemplate(AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION);
            }
            if ($this->request->post('votePolicy') !== null) {
                $policyData = $this->request->post('votePolicy', []);
                $votingBlock->setVotingPolicy($this->getPolicyFromUpdateData(
                    $votingBlock,
                    intval($policyData['id']),
                    $policyData['user_groups'] ?? []
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
            foreach ($motion->getVisibleAmendmentsSorted(false, false) as $amendment) {
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
        $question->title = \Yii::$app->request->post('question', '-');
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
            if ($item->votingBlockId === $votingBlock->id) {
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
    private function voteForSingleItem(User $user, VotingBlock $votingBlock, IVotingItem $item, int $public, string $voteChoice): Vote {
        if (!$votingBlock->userIsCurrentlyAllowedToVoteFor($user, $item)) {
            throw new FormError('Not possible to vote for this item');
        }

        $vote = new Vote();
        $vote->userId = $user->id;
        $vote->votingBlockId = $votingBlock->id;
        $vote->setVoteFromApi($voteChoice, $votingBlock->getAnswers());
        $vote->motionId = (is_a($item, Motion::class) ? $item->id : null);
        $vote->amendmentId = (is_a($item, Amendment::class) ? $item->id : null);
        $vote->questionId = (is_a($item, VotingQuestion::class) ? $item->id : null);

        // $public should be the same as votesPublic, as it was cached in the frontend and is sent from it as-is.
        // This is just a safeguard so that an accidental change in the value in the database does not lead to
        // a vote cast by the user under the assumption of being non-public accidentally being stored as public
        $vote->public = min($public, $votingBlock->votesPublic);

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

    private function voteForItemGroup(User $user, VotingBlock $votingBlock, string $itemGroup, int $public, string $voteChoice): void {
        $votes = [];
        foreach ($votingBlock->getItemGroupItems($itemGroup) as $imotion) {
            $votes[] = $this->voteForSingleItem($user, $votingBlock, $imotion, $public, $voteChoice);
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

    /**
     * @throws FormError
     */
    public function userVote(VotingBlock $votingBlock, User $user): void
    {
        foreach ($this->request->post('votes', []) as $voteData) {
            $public = isset($voteData['public']) ? intval($voteData['public']) : VotingBlock::VOTES_PUBLIC_NO;
            if (isset($voteData['itemGroupSameVote']) && trim($voteData['itemGroupSameVote']) !== '') {
                ResourceLock::lockVotingBlockItemGroup($votingBlock, $voteData['itemGroupSameVote']);
                if ($voteData['vote'] === 'undo') {
                    $this->undoVoteForItemGroup($user, $votingBlock, $voteData['itemGroupSameVote']);
                } else {
                    $this->voteForItemGroup($user, $votingBlock, $voteData['itemGroupSameVote'], $public, $voteData['vote']);
                }
                ResourceLock::unlockVotingBlockItemGroup($votingBlock, $voteData['itemGroupSameVote']);
            } else {
                // Vote for a single item that is not assigned to a item group
                if (!in_array($voteData['itemType'], ['motion', 'amendment', 'question'])) {
                    throw new FormError('Invalid vote');
                }
                $item = $this->getVotingItemByTypeAndId($voteData['itemType'], intval($voteData['itemId']), $votingBlock);
                ResourceLock::lockVotingItemForVoting($item);
                if ($voteData['vote'] === 'undo') {
                    $this->undoVoteForSingleItem($user, $votingBlock, $item);
                } else {
                    $vote = $this->voteForSingleItem($user, $votingBlock, $item, $public, $voteData['vote']);
                    $vote->save();
                }
                ResourceLock::unlockVotingItemForVoting($item);
            }
        }
    }

    public function getOpenVotingsForUser(?Motion $assignedToMotion, User $user): array
    {
        $votingData = [];
        foreach (Factory::getOpenVotingBlocks($this->consultation, $assignedToMotion) as $voting) {
            $votingData[] = $voting->getUserVotingApiObject($user);
        }
        return $votingData;
    }

    public function getClosedPublishedVotingsForUser(User $user): array
    {
        $votingData = [];
        foreach (Factory::getPublishedClosedVotingBlocks($this->consultation) as $voting) {
            $votingData[] = $voting->getUserResultsApiObject($user);
        }
        return $votingData;
    }

    /**
     * @param int[] $votingIds
     */
    public function sortVotings(array $votingIds): void
    {
        $positionById = [];
        for ($pos = 0; $pos < count($votingIds); $pos++) {
            $positionById[$votingIds[$pos]] = $pos;
        }
        $firstUnusedPos = $pos;

        foreach ($this->consultation->votingBlocks as $votingBlock) {
            if (isset($positionById[$votingBlock->id])) {
                $votingBlock->position = $positionById[$votingBlock->id];
            } else {
                $votingBlock->position = $firstUnusedPos;
                $firstUnusedPos++;
            }
            $votingBlock->save();
        }
    }
}
