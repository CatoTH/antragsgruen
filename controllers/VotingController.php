<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\db\User;
use app\models\db\Vote;
use app\models\db\VotingBlock;
use app\models\proposedProcedure\Factory;
use yii\web\Response;

class VotingController extends Base
{
    // *** Shared methods ***

    private function getError(string $message): string
    {
        return json_encode([
            'success' => false,
            'message' => $message,
        ]);
    }

    // *** User-facing methods ***

    public function actionGetOpenVotingBlocks()
    {
        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        $proposalFactory = new Factory($this->consultation, false);
        $votingData = [];
        foreach ($proposalFactory->getOpenVotingBlocks() as $voting) {
            $votingData[] = $voting->getUserApiObject($user);
        }

        $responseJson = json_encode($votingData);

        return $this->returnRestResponse(200, $responseJson);
    }

    /**
     * votes[0][itemType]=amendment
     * votes[0][itemType]=amendment
     * votes[0][vote]=yes
     */
    public function actionPostVote($votingBlockId)
    {
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

        $votesToSave = [];
        foreach (\Yii::$app->request->post('votes', []) as $voteData) {
            if (!in_array($voteData['itemType'], ['motion', 'amendment'])) {
                return $this->getError('Invalid vote');
            }

            if ($votingBlock->getUserVote($user, $voteData['itemType'], $voteData['itemId'])) {
                return $this->getError('Already voted');
            }
            if (!$votingBlock->userIsAllowedToVoteFor($user, $voteData['itemType'], $voteData['itemId'])) {
                return $this->getError('Not possible to vote for this item');
            }

            $vote = new Vote();
            $vote->userId = $user->id;
            $vote->votingBlockId = $votingBlock->id;

            if ($voteData['vote'] === 'yes') {
                $vote->vote = Vote::VOTE_YES;
            } elseif ($voteData['vote'] === 'no') {
                $vote->vote = Vote::VOTE_NO;
            } elseif ($voteData['vote'] === 'abstention') {
                $vote->vote = Vote::VOTE_ABSTENTION;
            } else {
                return $this->getError('Invalid vote');
            }
            if ($voteData['itemType'] === 'motion') {
                $vote->motionId = intval($voteData['itemId']);
                $vote->amendmentId = null;
            }
            if ($voteData['itemType'] === 'amendment') {
                $vote->motionId = null;
                $vote->amendmentId = intval($voteData['itemId']);
            }
            if (isset($voteData['public']) && $voteData['public'] && $votingBlock->votesPublic) {
                $vote->public = 1;
            } else {
                $vote->public = 0;
            }
            $vote->dateVote = date('Y-m-d H:i:s');

            $votesToSave[] = $vote;
        }

        foreach ($votesToSave as $vote) {
            $vote->save();
        }

        return $this->actionGetOpenVotingBlocks();
    }

}
