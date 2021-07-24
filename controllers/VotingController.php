<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\db\User;
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

        $user       = User::getCurrentUser();
        $proposalFactory = new Factory($this->consultation, false);
        $votingData = [];
        foreach ($proposalFactory->getOpenVotingBlocks() as $voting) {
            $votingData[] = $voting->getUserApiObject($user);
        }

        $responseJson = json_encode($votingData);
        return $this->returnRestResponse(200, $responseJson);
    }
}
