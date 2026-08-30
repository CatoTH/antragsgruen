<?php

declare(strict_types=1);

namespace app\controllers\rest;

use app\models\exceptions\ApiResponseException;
use app\models\http\RestApiResponse;
use app\models\quorumType\IQuorumType;
use app\models\settings\Privileges;
use app\models\db\{User, VotingBlock, VotingQuestion};
use app\components\{LiveTools, ResourceLock, Tools, UserGroupAdminMethods, VotingMethods};
use app\models\api\voting\{VotingBlockAdmin, VotingBlockUser};
use app\models\proposedProcedure\Factory;

/**
 * Everything the voting widgets read and write. Authenticated by JWT like the other REST endpoints:
 * the widgets poll these while a voting is running, and a session-authenticated poll would keep
 * renewing the CSRF cookie of the page it is polling from (see RestBase).
 *
 * The one thing that stayed behind in app\controllers\VotingController is the result download - a
 * link the browser follows, which cannot carry a bearer token.
 */
class VotingController extends RestBase
{
    public const VIEW_ID_GET_OPEN_VOTING_BLOCKS = 'get-open-voting-blocks';
    public const VIEW_ID_GET_ADMIN_VOTING_BLOCKS = 'get-admin-voting-blocks';


    private VotingMethods $votingMethods;
    private UserGroupAdminMethods $userGroupMethods;

    public function beforeAction($action): bool
    {
        $result = parent::beforeAction($action);

        if ($result) {
            $this->votingMethods = new VotingMethods();
            $this->votingMethods->setRequestData($this->consultation, $this->getHttpRequest());

            $this->userGroupMethods = new UserGroupAdminMethods();
            $this->userGroupMethods->setRequestData($this->consultation, $this->getHttpRequest(), $this->getHttpSession());
        }

        return $result;
    }

    // *** Admin-facing methods ***

    /**
     * @throws \Exception
     */
    private function ensureAdminPermissions(): void
    {
        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_VOTINGS, null)) {
            throw new \Exception('Missing privileges');
        }
    }

    /**
     * @throws \Exception
     */
    private function getVotingBlockAndCheckAdminPermission(string $votingBlockId): VotingBlock
    {
        $this->ensureAdminPermissions();

        $block = $this->consultation->getVotingBlock(intval($votingBlockId));
        if (!$block) {
            throw new \Exception('Voting block not found');
        }

        return $block;
    }

    /**
     * @return VotingBlockAdmin[]
     */
    private function getAllVotingAdminData(): array
    {
        $this->consultation->refresh();

        $apiData = [];
        foreach (Factory::getAllVotingBlocks($this->consultation) as $votingBlock) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $apiData[] = $votingBlock->getAdminApiObject(User::getCurrentUser());
        }

        return $apiData;
    }

    /**
     * @param VotingBlockAdmin[]|VotingBlockUser[]|array $payload
     */
    private function returnVotingData(array $payload): RestApiResponse
    {
        return new RestApiResponse(200, null, Tools::getSerializer()->serialize($payload, 'json'));
    }

    public function actionGetAdminVotingBlocks(): RestApiResponse
    {
        $this->handleRestHeaders(['GET'], true);
        try {
            $this->ensureAdminPermissions();
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        return $this->returnVotingData($this->getAllVotingAdminData());
    }

    public function actionPostVoteSettings(string $votingBlockId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);

        try {
            $votingBlock = $this->getVotingBlockAndCheckAdminPermission($votingBlockId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }
        ResourceLock::lockVotingBlockForWrite($votingBlock);

        switch ($this->getPostValue('op')) {
            case 'update-status':
                $this->votingMethods->voteStatusUpdate($votingBlock);
                break;
            case 'save-settings':
                $this->votingMethods->voteSaveSettings($votingBlock);
                break;
            case 'add-imotion':
                $this->votingMethods->voteAddIMotion($votingBlock);
                break;
            case 'add-question':
                $this->votingMethods->voteAddQuestion($votingBlock);
                break;
            case 'remove-item':
                $this->votingMethods->voteRemoveItem($votingBlock);
                break;
            case 'delete-voting':
                $this->votingMethods->deleteVoting($votingBlock);
                break;
            case 'set-voters-to-user-group':
                $userIds = array_map('intval', $this->getPostValue('userIds', []));
                $groupId = intval($this->getPostValue('newUserGroup'));
                $this->userGroupMethods->setUserGroupUsers($groupId, $userIds);
                break;
        }

        $responseData = $this->getAllVotingAdminData();

        ResourceLock::releaseAllLocks();

        // After the locks: the event is a copy of a state that is saved, and holding a lock while
        // talking to the broker would make every voter wait for it
        LiveTools::sendVotingState($this->consultation, $votingBlock);

        return $this->returnVotingData($responseData);
    }

    public function actionPostVoteOrder(): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $this->ensureAdminPermissions();
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        $votingIds = array_values(array_map('intval', $this->getPostValue('votingIds')));
        $this->votingMethods->sortVotings($votingIds);

        return $this->returnVotingData($this->getAllVotingAdminData());
    }

    public function actionCreateVotingBlock(): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $this->ensureAdminPermissions();
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        $newBlock = new VotingBlock();
        $newBlock->consultationId = $this->consultation->id;
        $newBlock->position = VotingBlock::getNextAvailablePosition($this->consultation);
        $newBlock->setTitle($this->getPostValue('title', ''));
        $newBlock->majorityType = intval($this->getPostValue('majorityType'));
        $newBlock->quorumType = intval($this->getPostValue('quorumType', IQuorumType::QUORUM_TYPE_NONE));
        $newBlock->votesPublic = intval($this->getPostValue('votesPublic'));
        $newBlock->resultsPublic = intval($this->getPostValue('resultsPublic'));
        if ($this->getPostValue('assignedMotion') !== null && $this->getPostValue('assignedMotion') > 0) {
            $newBlock->assignedToMotionId = $this->getPostValue('assignedMotion');
        } else {
            $newBlock->assignedToMotionId = null;
        }
        $newBlock->setAnswerTemplate(intval($this->getPostValue('answers')));
        $newBlock->setVotingPolicy($this->votingMethods->getPolicyFromUpdateData(
            $newBlock,
            intval($this->getPostValue('votePolicy')),
            $this->getPostValue('userGroups', [])
        ));
        // If the voting is created from the proposed procedure, we assume it's only used to show it there
        $newBlock->votingStatus = VotingBlock::STATUS_PREPARING;

        $settings = $newBlock->getSettings();
        $settings->votesNames = intval($this->getPostValue('votesNames', \app\models\settings\VotingBlock::VOTES_NAMES_AUTH));
        $newBlock->setSettings($settings);

        $newBlock->save();

        if ($this->getPostValue('type') === 'question') {
            $question = new VotingQuestion();
            $question->consultationId = $newBlock->consultationId;
            $question->title = $this->getPostValue('specificQuestion', '-');
            $question->votingBlockId = $newBlock->id;
            $question->save();
        }

        LiveTools::sendVotingState($this->consultation, $newBlock);

        return new RestApiResponse(200, null, Tools::getSerializer()->serialize([
            'votings' => $this->getAllVotingAdminData(),
            'created_voting' => $newBlock->id,
        ], 'json'));
    }

    // *** User-facing methods ***

    public function actionGetOpenVotingBlocks(?int $assignedToMotionId, int $showAllOpen = 0): RestApiResponse
    {
        $this->handleRestHeaders(['GET'], true);
        if (!User::getCurrentUser()) {
            throw new ApiResponseException('Log logged in', 401);
        }

        if ($assignedToMotionId) {
            $assignedToMotion = $this->consultation->getMotion($assignedToMotionId);
        } else {
            $assignedToMotion = null;
        }

        $response = $this->votingMethods->getOpenVotingsForUser(($showAllOpen > 0), $assignedToMotion, User::getCurrentUser());

        return $this->returnVotingData($response);
    }

    public function actionGetClosedVotingBlocks(): RestApiResponse
    {
        $this->handleRestHeaders(['GET'], true);
        if (!User::getCurrentUser()) {
            throw new ApiResponseException('Log logged in', 401);
        }

        $response = $this->votingMethods->getClosedPublishedVotingsForUser(User::getCurrentUser());

        return $this->returnVotingData($response);
    }

    /**
     * votes: [{
     *     itemGroupSameVote: [empty]|"123abcderf",
     *     itemType: "amendment",
     *     itemId: 3,
     *     vote: "yes",
     *     public: 2 (optional)
     * }]
     * --
     * abstention: {
     *     abstain: true|false,
     *     public: 2 (optional)
     * }
     */
    public function actionPostVote(int $votingBlockId, ?int $assignedToMotionId, int $showAllOpen = 0): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);

        if ($assignedToMotionId) {
            $assignedToMotion = $this->consultation->getMotion($assignedToMotionId);
        } else {
            $assignedToMotion = null;
        }

        $votingBlock = $this->consultation->getVotingBlock($votingBlockId);
        if (!$votingBlock) {
            return $this->returnRestResponseFromException(new \Exception('Voting not found'));
        }
        ResourceLock::lockVotingBlockForRead($votingBlock);

        if ($votingBlock->votingStatus !== VotingBlock::STATUS_OPEN) {
            return $this->returnRestResponseFromException(new \Exception('Voting not open'));
        }
        $user = User::getCurrentUser();
        if (!$user) {
            throw new ApiResponseException('Log logged in', 401);
        }

        try {
            if ($this->getPostValue('abstention')) {
                $this->votingMethods->userSetAbstention($votingBlock, $user);
            } else {
                $this->votingMethods->userVote($votingBlock, $user);
            }
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        ResourceLock::releaseAllLocks();
        $votingBlock->refresh();

        LiveTools::sendVotingTally($this->consultation, $votingBlock);

        $response = $this->votingMethods->getOpenVotingsForUser(($showAllOpen > 0), $assignedToMotion, User::getCurrentUser());

        return $this->returnVotingData($response);
    }
}
