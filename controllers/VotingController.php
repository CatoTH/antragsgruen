<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\db\{Amendment, IMotion, Motion, User, Vote, VotingBlock};
use app\components\ResourceLock;
use app\models\exceptions\FormError;
use app\models\majorityType\IMajorityType;
use app\models\proposedProcedure\Factory;
use yii\web\Response;

class VotingController extends Base
{
    // *** Shared methods ***

    private function getError(string $message): string
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        return json_encode([
            'success' => false,
            'message' => $message,
        ]);
    }

    // *** Admin-facing methods ***

    private function getVotingBlockAndCheckAdminPermission(string $votingBlockId): VotingBlock
    {
        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, User::PRIVILEGE_VOTINGS)) {
            $this->returnRestResponse(403, $this->getError('Missing privileges'));
            die();
        }

        $block = $this->consultation->getVotingBlock(intval($votingBlockId));
        if (!$block) {
            $this->returnRestResponse(404, $this->getError('Voting block not found'));
            die();
        }

        return $block;
    }

    private function getAllVotingAdminData(): array
    {
        $this->consultation->refresh();

        $apiData = [];
        foreach (Factory::getAllVotingBlocks($this->consultation) as $votingBlock) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $apiData[] = $votingBlock->getAdminApiObject();
        }

        return $apiData;
    }

    public function actionGetAdminVotingBlocks()
    {
        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, User::PRIVILEGE_VOTINGS)) {
            $this->returnRestResponse(403, $this->getError('Missing privileges'));
            die();
        }

        $responseData = $this->getAllVotingAdminData();

        return $this->returnRestResponse(200, json_encode($responseData));
    }

    private function voteStatusUpdate(VotingBlock $votingBlock): void
    {
        if (in_array($votingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
            foreach (\Yii::$app->request->post('organizations', []) as $organization) {
                $users = ($organization['members_present'] !== '' ? intval($organization['members_present']) : null);
                $votingBlock->setUserPresentByOrganization($organization['id'], $users);
            }
            $votingBlock->save();
        }
        if (\Yii::$app->request->post('status') !== null) {
            $newStatus = intval(\Yii::$app->request->post('status'));
            if ($newStatus === VotingBlock::STATUS_PREPARING) {
                $votingBlock->switchToOnlineVoting();
            } elseif ($newStatus === VotingBlock::STATUS_OPEN) {
                $votingBlock->openVoting();
            } elseif ($newStatus === VotingBlock::STATUS_CLOSED) {
                $votingBlock->closeVoting();
            } elseif ($newStatus === VotingBlock::STATUS_OFFLINE) {
                $votingBlock->switchToOfflineVoting();
            }
        }
    }

    private function deleteVoting(VotingBlock $votingBlock)
    {
        $votingBlock->deleteVoting();
    }

    private function voteSaveSettings(VotingBlock $votingBlock): void
    {
        if (\Yii::$app->request->post('title')) {
            $votingBlock->title = \Yii::$app->request->post('title');
        }
        if (\Yii::$app->request->post('assignedMotion') !== null && \Yii::$app->request->post('assignedMotion') > 0) {
            $votingBlock->assignedToMotionId = \Yii::$app->request->post('assignedMotion');
        } else {
            $votingBlock->assignedToMotionId = null;
        }
        if (\Yii::$app->request->post('resultsPublic') !== null) {
            $votingBlock->resultsPublic = intval(\Yii::$app->request->post('resultsPublic'));
        } else {
            $votingBlock->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
        }
        if (in_array($votingBlock->votingStatus, [VotingBlock::STATUS_OFFLINE, VotingBlock::STATUS_PREPARING])) {
            if (\Yii::$app->request->post('votesPublic') !== null) {
                $votingBlock->votesPublic = intval(\Yii::$app->request->post('votesPublic'));
            } else {
                $votingBlock->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
            }
            if (\Yii::$app->request->post('majorityType') !== null) {
                $votingBlock->majorityType = intval(\Yii::$app->request->post('majorityType'));
            } else {
                $votingBlock->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
            }
        }

        $votingBlock->save();
    }

    private function voteAddItem(VotingBlock $votingBlock): void
    {
        if ($votingBlock->votingStatus !== VotingBlock::STATUS_PREPARING) {
            throw new FormError('Not possible to remove items in this state');
        }
        /** @var IMotion[] $items */
        $items = [];
        $idParts = explode('-', \Yii::$app->request->post('itemDefinition', ''));

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

    private function voteRemoveItem(VotingBlock $votingBlock): void
    {
        if ($votingBlock->votingStatus !== VotingBlock::STATUS_PREPARING) {
            throw new FormError('Not possible to remove items in this state');
        }
        $item = null;
        if (\Yii::$app->request->post('itemType') === 'motion') {
            $item = $this->consultation->getMotion(\Yii::$app->request->post('itemId'));
        }
        if (\Yii::$app->request->post('itemType') === 'amendment') {
            $item = $this->consultation->getAmendment(\Yii::$app->request->post('itemId'));
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

    public function actionPostVoteSettings(string $votingBlockId)
    {
        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $votingBlock = $this->getVotingBlockAndCheckAdminPermission($votingBlockId);
        ResourceLock::lockVotingBlockForWrite($votingBlock);

        switch (\Yii::$app->request->post('op')) {
            case 'update-status':
                $this->voteStatusUpdate($votingBlock);
                break;
            case 'save-settings':
                $this->voteSaveSettings($votingBlock);
                break;
            case 'add-item':
                $this->voteAddItem($votingBlock);
                break;
            case 'remove-item':
                $this->voteRemoveItem($votingBlock);
                break;
            case 'delete-voting':
                $this->deleteVoting($votingBlock);
                break;
        }

        $responseData = $this->getAllVotingAdminData();

        ResourceLock::releaseAllLocks();

        return $this->returnRestResponse(200, json_encode($responseData));
    }

    public function actionCreateVotingBlock()
    {
        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, User::PRIVILEGE_VOTINGS)) {
            $this->returnRestResponse(403, $this->getError('Missing privileges'));
            die();
        }

        $newBlock = new VotingBlock();
        $newBlock->consultationId = $this->consultation->id;
        $newBlock->title = \Yii::$app->request->post('title');
        $newBlock->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
        $newBlock->votesPublic = VotingBlock::VOTES_PUBLIC_NO;
        $newBlock->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
        if (\Yii::$app->request->post('assignedMotion') !== null && \Yii::$app->request->post('assignedMotion') > 0) {
            $newBlock->assignedToMotionId = \Yii::$app->request->post('assignedMotion');
        } else {
            $newBlock->assignedToMotionId = null;
        }
        // If the voting is created from the proposed procedure, we assume it's only used to show it there
        $newBlock->votingStatus = VotingBlock::STATUS_PREPARING;
        $newBlock->save();

        $votingData = $this->getAllVotingAdminData();

        return $this->returnRestResponse(200, json_encode([
            'votings' => $votingData,
            'created_voting' => $newBlock->id,
        ]));
    }

    // *** User-facing methods ***

    private function getOpenVotingsUserData(?Motion $assignedToMotion): string
    {
        $user = User::getCurrentUser();
        $votingData = [];
        foreach (Factory::getOpenVotingBlocks($this->consultation, $assignedToMotion) as $voting) {
            $votingData[] = $voting->getUserVotingApiObject($user);
        }

        return json_encode($votingData);
    }

    private function getClosedVotingsUserData(): string
    {
        $user = User::getCurrentUser();
        $votingData = [];
        foreach (Factory::getClosedVotingBlocks($this->consultation) as $voting) {
            $votingData[] = $voting->getUserResultsApiObject($user);
        }

        return json_encode($votingData);
    }

    public function actionGetOpenVotingBlocks($assignedToMotionId)
    {
        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        if ($assignedToMotionId) {
            $assignedToMotion = $this->consultation->getMotion($assignedToMotionId);
        } else {
            $assignedToMotion = null;
        }

        $responseJson = $this->getOpenVotingsUserData($assignedToMotion);

        return $this->returnRestResponse(200, $responseJson);
    }

    public function actionGetClosedVotingBlocks()
    {
        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $responseJson = $this->getClosedVotingsUserData();

        return $this->returnRestResponse(200, $responseJson);
    }

    private function getIMotionByTypeAndId(string $itemType, int $itemId, ?VotingBlock $ensureVotingBlock): IMotion
    {
        $item = null;
        if ($itemType === 'amendment') {
            $item = $this->consultation->getAmendment($itemId);
        }
        if ($itemType === 'motion') {
            $item = $this->consultation->getMotion($itemId);
        }
        if ($item) {
            if ($ensureVotingBlock && $item->votingBlockId !== $ensureVotingBlock->id) {
                throw new FormError('Item not part of this voting block');
            }
            return $item;
        } else {
            throw new FormError('Item not found');
        }
    }

    private function voteForSingleItem(User $user, VotingBlock $votingBlock, IMotion $imotion, int $public, string $voteChoice): Vote {
        if (!$votingBlock->userIsCurrentlyAllowedToVoteFor($user, $imotion)) {
            throw new FormError('Not possible to vote for this item');
        }

        $vote = new Vote();
        $vote->userId = $user->id;
        $vote->votingBlockId = $votingBlock->id;
        $vote->setVoteFromApi($voteChoice);
        if ($vote->vote === null) {
            throw new FormError('Invalid vote');
        }
        if (is_a($imotion, Motion::class)) {
            $vote->motionId = $imotion->id;
            $vote->amendmentId = null;
        }
        if (is_a($imotion, Amendment::class)) {
            $vote->motionId = null;
            $vote->amendmentId = $imotion->id;
        }

        // $public should be the same as votesPublic, as it was cached in the frontend and is sent from it as-is.
        // This is just a safeguard so that an accidental change in the value in the database does not lead to
        // a vote cast by the user under the assumption of being non-public accidentally being stored as public
        $vote->public = min($public, $votingBlock->votesPublic);

        $vote->dateVote = date('Y-m-d H:i:s');

        return $vote;
    }

    private function undoVoteForSingleItem(User $user, VotingBlock $votingBlock, IMotion $imotion): void {
        $exitingVote = $votingBlock->getUserSingleItemVote($user, $imotion);
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
     * votes[0][itemGroupSameVote]=[empty]|123abcderf
     * votes[0][itemType]=amendment
     * votes[0][itemId]=3
     * votes[0][vote]=yes
     * [optional] votes[0][public]=1
     */
    public function actionPostVote($votingBlockId, $assignedToMotionId)
    {
        $this->handleRestHeaders(['POST'], true);

        if ($assignedToMotionId) {
            $assignedToMotion = $this->consultation->getMotion($assignedToMotionId);
        } else {
            $assignedToMotion = null;
        }

        $votingBlock = $this->consultation->getVotingBlock(intval($votingBlockId));
        if (!$votingBlock) {
            return $this->getError('Voting not found');
        }
        ResourceLock::lockVotingBlockForRead($votingBlock);

        if ($votingBlock->votingStatus !== VotingBlock::STATUS_OPEN) {
            return $this->getError('Voting not open');
        }
        $user = User::getCurrentUser();
        if (!$user) {
            return $this->getError('Not logged in');
        }

        try {
            foreach (\Yii::$app->request->post('votes', []) as $voteData) {
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
                    if (!in_array($voteData['itemType'], ['motion', 'amendment'])) {
                        return $this->getError('Invalid vote');
                    }
                    $item = $this->getIMotionByTypeAndId($voteData['itemType'], intval($voteData['itemId']), $votingBlock);
                    ResourceLock::lockIMotionItemForVoting($item);
                    if ($voteData['vote'] === 'undo') {
                        $this->undoVoteForSingleItem($user, $votingBlock, $item);
                    } else {
                        $vote = $this->voteForSingleItem($user, $votingBlock, $item, $public, $voteData['vote']);
                        $vote->save();
                    }
                    ResourceLock::unlockIMotionItemForVoting($item);
                }
            }
        } catch (FormError $error) {
            return $this->getError($error->getMessage());
        }

        ResourceLock::releaseAllLocks();
        $votingBlock->refresh();

        $responseJson = $this->getOpenVotingsUserData($assignedToMotion);

        return $this->returnRestResponse(200, $responseJson);
    }
}
