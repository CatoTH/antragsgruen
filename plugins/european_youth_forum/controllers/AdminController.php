<?php

namespace app\plugins\european_youth_forum\controllers;

use app\components\UrlHelper;
use app\controllers\Base;
use app\models\majorityType\IMajorityType;
use app\models\policies\UserGroups;
use app\models\quorumType\IQuorumType;
use app\models\settings\Privileges;
use app\models\votings\AnswerTemplates;
use app\plugins\european_youth_forum\VotingHelper;
use app\models\db\{ConsultationUserGroup, User, VotingBlock, VotingQuestion};

class AdminController extends Base
{
    private function ensureVotingAdminPermissions(): void
    {
        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_VOTINGS, null)) {
            $this->getHttpSession()->setFlash('error', 'Not allowed to access this page');
            $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
            die();
        }
    }

    public function actionCreateYfjVoting(): void
    {
        $this->ensureVotingAdminPermissions();

        $data = $this->getPostValue('voting', []);
        if (!is_numeric($data['number']) || !$data['title'] || !in_array($data['type'], ['question', 'motions'])) {
            $this->getHttpSession()->setFlash('error', 'Not all values were provided to create a Voting');
            $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
        }

        $rcNumber = intval($data['number']);

        $groupNyc = null;
        $groupIngyo = null;
        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($userGroup->title === 'Voting ' . $rcNumber . ': NYC') {
                $groupNyc = $userGroup;
            }
            if ($userGroup->title === 'Voting ' . $rcNumber . ': INGYO') {
                $groupIngyo = $userGroup;
            }
        }
        if (!$groupNyc || !$groupIngyo) {
            $this->getHttpSession()->setFlash('error', 'Could not find the two voting groups for this Roll Call');
            $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
        }

        $newBlock = new VotingBlock();
        $newBlock->consultationId = $this->consultation->id;
        $newBlock->position = VotingBlock::getNextAvailablePosition($this->consultation);
        $newBlock->setTitle($data['title']);
        $newBlock->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
        $newBlock->quorumType = IQuorumType::QUORUM_TYPE_NONE;
        $newBlock->votesPublic = VotingBlock::VOTES_PUBLIC_ALL;
        $newBlock->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
        $newBlock->assignedToMotionId = null;
        $newBlock->setAnswerTemplate(AnswerTemplates::TEMPLATE_YES_NO_ABSTENTION);
        $newBlock->setVotingPolicy(new UserGroups($this->consultation, $newBlock, ['userGroups' => [$groupNyc->id, $groupIngyo->id]]));
        $newBlock->votingStatus = VotingBlock::STATUS_PREPARING;
        $newBlock->save();

        if ($data['type'] === 'question') {
            $question = new VotingQuestion();
            $question->consultationId = $newBlock->consultationId;
            $question->title = $data['title'];
            $question->votingBlockId = $newBlock->id;
            $question->save();
        }

        $this->getHttpSession()->setFlash('success', 'The Voting was created');

        $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
    }

    private function findRollCallNumber(int $number): ?VotingBlock
    {
        foreach ($this->consultation->votingBlocks as $votingBlock) {
            if ($votingBlock->votingStatus === VotingBlock::STATUS_DELETED) {
                continue;
            }
            if (str_starts_with($votingBlock->title, 'Roll Call ')) {
                $rcNumber = explode(" ", explode('Roll Call ', $votingBlock->title)[1])[0];
                if (intval($rcNumber) === $number) {
                    return $votingBlock;
                }
            }
        }

        return null;
    }

    public function actionCreateRollCall(): void
    {
        $this->ensureVotingAdminPermissions();

        $data = $this->getPostValue('rollcall', []);
        if (!is_numeric($data['number'])) {
            $this->getHttpSession()->setFlash('error', 'Not all values were provided to create a Roll Call');
            $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
        }

        $createGroups = isset($data['create_groups']);
        $rcNumber = intval($data['number']);
        if ($this->findRollCallNumber($rcNumber)) {
            $this->getHttpSession()->setFlash('error', 'There already exists a Roll Call with this number');
            $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
        }

        $userGroupIds = [];
        foreach ($this->consultation->getAllAvailableUserGroups() as $userGroup) {
            if ($createGroups && $userGroup->title === 'Voting ' . $rcNumber . ': NYC') {
                $this->getHttpSession()->setFlash('error', 'The user group to be created already exists: Voting ' . $rcNumber . ': NYC');
                $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
            }
            if ($createGroups && $userGroup->title === 'Voting ' . $rcNumber . ': INGYO') {
                $this->getHttpSession()->setFlash('error', 'The user group to be created already exists: Voting ' . $rcNumber . ': INGYO');
                $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
            }
            if (VotingHelper::conditionShouldBeAssignedToRollCall($userGroup)) {
                $userGroupIds[] = $userGroup->id;
            }
        }

        $rcTitle = 'Roll Call ' . $rcNumber;
        if ($data['name']) {
            $rcTitle .= ' (' . $data['name'] . ')';
        }

        $newBlock = new VotingBlock();
        $newBlock->consultationId = $this->consultation->id;
        $newBlock->position = VotingBlock::getNextAvailablePosition($this->consultation);
        $newBlock->setTitle($rcTitle);
        $newBlock->majorityType = IMajorityType::MAJORITY_TYPE_SIMPLE;
        $newBlock->quorumType = IQuorumType::QUORUM_TYPE_HALF;
        $newBlock->votesPublic = VotingBlock::VOTES_PUBLIC_ALL;
        $newBlock->resultsPublic = VotingBlock::RESULTS_PUBLIC_YES;
        $newBlock->assignedToMotionId = null;
        $newBlock->setAnswerTemplate(AnswerTemplates::TEMPLATE_PRESENT);
        $newBlock->setVotingPolicy(new UserGroups($this->consultation, $newBlock, ['userGroups' => $userGroupIds]));
        $newBlock->votingStatus = VotingBlock::STATUS_PREPARING;
        $newBlock->save();

        $question = new VotingQuestion();
        $question->consultationId = $newBlock->consultationId;
        $question->title = 'Are you present?';
        $question->votingBlockId = $newBlock->id;
        $question->save();

        if ($createGroups) {
            $userGroup = new ConsultationUserGroup();
            $userGroup->title = 'Voting ' . $rcNumber . ': NYC';
            $userGroup->consultationId = $this->consultation->id;
            $userGroup->siteId = $this->consultation->siteId;
            $userGroup->position = 0;
            $userGroup->selectable = 1;
            $userGroup->save();

            $userGroup = new ConsultationUserGroup();
            $userGroup->title = 'Voting ' . $rcNumber . ': INGYO';
            $userGroup->consultationId = $this->consultation->id;
            $userGroup->siteId = $this->consultation->siteId;
            $userGroup->position = 0;
            $userGroup->selectable = 1;
            $userGroup->save();
        }

        $this->getHttpSession()->setFlash('success', 'The Roll Call was created');

        $this->redirect(UrlHelper::createUrl('/consultation/admin-votings'));
    }
}
