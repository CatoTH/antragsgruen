<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\db\{Amendment, IMotion, Motion, User, Vote, VotingBlock};
use app\models\exceptions\FormError;
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
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

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

    private function getAllVotingAdminData(): string
    {
        $apiData = [];
        foreach (Factory::getAllVotingBlocks($this->consultation) as $votingBlock) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $apiData[] = $votingBlock->getAdminApiObject();
        }

        return json_encode($apiData);
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

        $responseJson = $this->getAllVotingAdminData();

        return $this->returnRestResponse(200, $responseJson);
    }

    private function voteSettingsUpdate(VotingBlock $votingBlock): void
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

                foreach ($this->consultation->votingBlocks as $otherVotingBlock) {
                    if ($otherVotingBlock->votingStatus === VotingBlock::STATUS_OPEN && $votingBlock->id !== $otherVotingBlock->id) {
                        $otherVotingBlock->closeVoting();
                    }
                }
            } elseif ($newStatus === VotingBlock::STATUS_CLOSED) {
                $votingBlock->closeVoting();
            } elseif ($newStatus === VotingBlock::STATUS_OFFLINE) {
                $votingBlock->switchToOfflineVoting();
            }
        }
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

        switch (\Yii::$app->request->post('op')) {
            case 'update':
                $this->voteSettingsUpdate($votingBlock);
                break;
            case 'add-item':
                $this->voteAddItem($votingBlock);
                break;
            case 'remove-item':
                $this->voteRemoveItem($votingBlock);
                break;
        }

        $responseJson = $this->getAllVotingAdminData();

        return $this->returnRestResponse(200, $responseJson);
    }

    // *** User-facing methods ***

    private function getOpenVotingsUserData(): string
    {
        $user = User::getCurrentUser();
        $votingData = [];
        foreach (Factory::getOpenVotingBlocks($this->consultation) as $voting) {
            $votingData[] = $voting->getUserApiObject($user);
        }

        return json_encode($votingData);
    }

    public function actionGetOpenVotingBlocks()
    {
        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $responseJson = $this->getOpenVotingsUserData();

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

    private function voteForSingleItem(User $user, VotingBlock $votingBlock, IMotion $imotion, bool $public, string $voteChoice): Vote {
        if (!$votingBlock->userIsAllowedToVoteFor($user, $imotion)) {
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
        if ($public && $votingBlock->votesPublic) {
            $vote->public = 1;
        } else {
            $vote->public = 0;
        }
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

    /**
     * @return Vote[]
     */
    private function voteForItemGroup(User $user, VotingBlock $votingBlock, string $itemGroup, bool $public, string $voteChoice): array {
        $votes = [];
        foreach ($votingBlock->getItemGroupItems($itemGroup) as $imotion) {
            $votes[] = $this->voteForSingleItem($user, $votingBlock, $imotion, $public, $voteChoice);
        }
        return $votes;
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
    public function actionPostVote($votingBlockId)
    {
        $this->handleRestHeaders(['POST'], true);

        $votingBlock = $this->consultation->getVotingBlock(intval($votingBlockId));
        if (!$votingBlock) {
            return $this->getError('Voting not found');
        }
        if ($votingBlock->votingStatus !== VotingBlock::STATUS_OPEN) {
            return $this->getError('Voting not open');
        }
        $user = User::getCurrentUser();
        if (!$user) {
            return $this->getError('Not logged in');
        }

        try {
            $votesToSave = [];
            foreach (\Yii::$app->request->post('votes', []) as $voteData) {
                $public = (isset($voteData['public']) && $voteData['public']);
                if (isset($voteData['itemGroupSameVote']) && trim($voteData['itemGroupSameVote']) !== '') {
                    if ($voteData['vote'] === 'undo') {
                        $this->undoVoteForItemGroup($user, $votingBlock, $voteData['itemGroupSameVote']);
                    } else {
                        $votesToSave = array_merge(
                            $votesToSave,
                            $this->voteForItemGroup($user, $votingBlock, $voteData['itemGroupSameVote'], $public, $voteData['vote'])
                        );
                    }
                } else {
                    // Vote for a single item that is not assigned to a item group
                    if (!in_array($voteData['itemType'], ['motion', 'amendment'])) {
                        return $this->getError('Invalid vote');
                    }
                    $item = $this->getIMotionByTypeAndId($voteData['itemType'], intval($voteData['itemId']), $votingBlock);
                    if ($voteData['vote'] === 'undo') {
                        $this->undoVoteForSingleItem($user, $votingBlock, $item);
                    } else {
                        $votesToSave[] = $this->voteForSingleItem($user, $votingBlock, $item, $public, $voteData['vote']);
                    }
                }
            }

            foreach ($votesToSave as $vote) {
                $vote->save();
            }
        } catch (FormError $error) {
            return $this->getError($error->getMessage());
        }

        $responseJson = $this->getOpenVotingsUserData();

        return $this->returnRestResponse(200, $responseJson);
    }
}
