<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\proposedProcedure\AgendaVoting;
use app\models\db\{ConsultationUserGroup, Motion, User, VotingBlock, VotingQuestion};
use app\components\{ResourceLock, UserGroupAdminMethods, VotingMethods};
use app\models\proposedProcedure\Factory;
use yii\web\{Application, Response};

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
            /** @var Application $app */
            $app = \Yii::$app;
            $this->votingMethods = new VotingMethods();
            $this->votingMethods->setRequestData($this->consultation, $app->request);

            $this->userGroupMethods = new UserGroupAdminMethods();
            $this->userGroupMethods->setRequestData($this->consultation, $app->request, $app->session);
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

        switch (\Yii::$app->request->post('op')) {
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
                $userIds = array_map('intval', \Yii::$app->request->post('userIds', []));
                $groupId = intval(\Yii::$app->request->post('newUserGroup'));
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
        $newBlock->title = \Yii::$app->request->post('title');
        $newBlock->majorityType = intval(\Yii::$app->request->post('majorityType'));
        $newBlock->votesPublic = intval(\Yii::$app->request->post('votesPublic'));
        $newBlock->resultsPublic = intval(\Yii::$app->request->post('resultsPublic'));
        if (\Yii::$app->request->post('assignedMotion') !== null && \Yii::$app->request->post('assignedMotion') > 0) {
            $newBlock->assignedToMotionId = \Yii::$app->request->post('assignedMotion');
        } else {
            $newBlock->assignedToMotionId = null;
        }
        $newBlock->setAnswerTemplate(intval(\Yii::$app->request->post('answers')));
        $newBlock->setVotingPolicy($this->votingMethods->getPolicyFromUpdateData(
            $newBlock,
            intval(\Yii::$app->request->post('votePolicy')),
            \Yii::$app->request->post('userGroups', [])
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
        switch ($format) {
            case 'ods':
                \Yii::$app->response->headers->add('Content-Type', 'application/vnd.oasis.opendocument.text');
                \Yii::$app->response->headers->add('Content-disposition', 'filename="voting-results.ods"');
                break;
            case 'xlsx':
                \Yii::$app->response->headers->add('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                \Yii::$app->response->headers->add('Content-disposition', 'filename="voting-results.xslx"');
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
