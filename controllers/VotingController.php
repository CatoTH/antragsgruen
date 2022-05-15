<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\proposedProcedure\AgendaVoting;
use app\models\quorumType\IQuorumType;
use app\models\db\{ConsultationUserGroup, Motion, User, VotingBlock, VotingQuestion};
use app\components\{ResourceLock, Tools, UserGroupAdminMethods, VotingMethods};
use app\models\proposedProcedure\Factory;
use yii\web\Response;

class VotingController extends Base
{
    /** @var VotingMethods */
    private $votingMethods;

    /** @var UserGroupAdminMethods */
    private $userGroupMethods;

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
        if (!$user || !$user->hasPrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_VOTINGS)) {
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
        if (!$user || !$user->hasPrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_VOTINGS)) {
            $this->returnRestResponse(403, $this->getError('Missing privileges'));
            die();
        }

        $responseData = $this->getAllVotingAdminData();

        return $this->returnRestResponse(200, json_encode($responseData));
    }

    public function actionPostVoteSettings(string $votingBlockId)
    {
        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $votingBlock = $this->getVotingBlockAndCheckAdminPermission($votingBlockId);
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

        return $this->returnRestResponse(200, json_encode($responseData));
    }

    public function actionCreateVotingBlock()
    {
        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, ConsultationUserGroup::PRIVILEGE_VOTINGS)) {
            $this->returnRestResponse(403, $this->getError('Missing privileges'));
            die();
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
        $newBlock->save();

        if ($this->getPostValue('type') === 'question') {
            $question = new VotingQuestion();
            $question->consultationId = $newBlock->consultationId;
            $question->title = $this->getPostValue('specificQuestion', '-');
            $question->votingBlockId = $newBlock->id;
            $question->save();
        }

        $votingData = $this->getAllVotingAdminData();

        return $this->returnRestResponse(200, json_encode([
            'votings' => $votingData,
            'created_voting' => $newBlock->id,
        ]));
    }

    public function actionDownloadVotingResults(string $votingBlockId, string $format)
    {
        $this->handleRestHeaders(['GET'], true);
        $votingBlock = $this->getVotingBlockAndCheckAdminPermission($votingBlockId);
        $agendaVoting = AgendaVoting::getFromVotingBlock($votingBlock);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        $fileNameBase = 'voting-results-' . Tools::sanitizeFilename($votingBlock->title, true);
        switch ($format) {
            case 'ods':
                \Yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
                \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($fileNameBase) . '.ods"');
                break;
            case 'xlsx':
                \Yii::$app->response->headers->add('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                \Yii::$app->response->headers->add('Content-disposition', 'filename="' . addslashes($fileNameBase) . '.xslx"');
                break;
            default:
                \Yii::$app->response->headers->add('Content-Type', 'text/html');
        }

        return $this->renderPartial('admin-download-results', ['agendaVoting' => $agendaVoting, 'format' => $format]);
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
            $this->votingMethods->userVote($votingBlock, $user);
        } catch (\Exception $e) {
            return $this->getError($e->getMessage());
        }

        ResourceLock::releaseAllLocks();
        $votingBlock->refresh();

        $responseJson = $this->getOpenVotingsUserData($assignedToMotion);

        return $this->returnRestResponse(200, $responseJson);
    }
}
