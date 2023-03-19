<?php

namespace app\controllers;

use app\components\VotingMethods;
use app\models\db\User;
use yii\web\{Request, Response};

class TestController extends Base
{
    public $enableCsrfValidation = false;
    public ?bool $allowNotLoggedIn = true;

    public function actionIndex(string $action = ''): string
    {
        if (YII_ENV !== 'test') {
            die("Only accessible in testing mode");
        }
        if ($_SERVER['REMOTE_ADDR'] !== '::1' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
            die("Only accessible from localhost");
        }

        $this->getHttpResponse()->format = Response::FORMAT_RAW;
        $this->getHttpResponse()->headers->add('Content-Type', 'application/json');

        switch ($action) {
            case 'set-amendment-status':
                return $this->actionSetAmendmentStatus();
            case 'set-user-fixed-data':
                return $this->actionSetUserFixedData();
            case 'user-votes':
                return $this->actionUserVotes();
        }

        return json_encode(['success' => false, 'message' => 'Unknown action: ' . $action], JSON_THROW_ON_ERROR);
    }

    /* Sample HTTP Request:

POST http://antragsgruen-test.local/stdparteitag/std-parteitag/test/set-amendment-status
Accept: application/json
Content-Type: application/x-www-form-urlencoded

id=270&status=3
     */
    private function actionSetAmendmentStatus(): string
    {
        $amendmentId = $this->getHttpRequest()->post('id');
        $status      = $this->getHttpRequest()->post('status');

        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            return json_encode(['success' => false, 'error' => 'Amendment not found'], JSON_THROW_ON_ERROR);
        }

        $amendment->status = intval($status);
        $amendment->save();

        return json_encode(['success' => true], JSON_THROW_ON_ERROR);
    }

    private function actionSetUserFixedData(): string
    {
        $user = User::findOne(['email' => $this->getHttpRequest()->post('email')]);
        if (!$user) {
            return json_encode(['success' => false, 'message' => 'user not found'], JSON_THROW_ON_ERROR);
        }
        $user->fixedData = ($this->getHttpRequest()->post('fixed') ? User::FIXED_NAME : 0);
        $user->nameFamily = $this->getHttpRequest()->post('nameFamily');
        $user->nameGiven = $this->getHttpRequest()->post('nameGiven');
        $user->name = $this->getHttpRequest()->post('nameGiven') . ' ' . $this->getHttpRequest()->post('nameFamily');
        $user->organization = $this->getHttpRequest()->post('organisation');
        $user->save();

        return json_encode(['success' => true], JSON_THROW_ON_ERROR);
    }

    private function actionUserVotes(): string
    {
        $user = User::findOne(['email' => $this->getHttpRequest()->post('email')]);
        if (!$user) {
            return json_encode(['success' => false, 'message' => 'user not found'], JSON_THROW_ON_ERROR);
        }

        $votingBlock = $this->consultation->getVotingBlock(intval($this->getHttpRequest()->post('votingBlock')));

        $postdata = [
            'votes' => [[
                'itemType' => 'question',
                'itemId' => $this->getHttpRequest()->post('itemId'),
                'vote' => $this->getHttpRequest()->post('answer'),
                'public' => 2,
            ]],
        ];

        $request = new class($postdata) extends Request {
            private ?array $postdata;

            public function __construct(?array $postdata, $config = [])
            {
                parent::__construct($config);
                $this->postdata = $postdata;
            }

            public function getBodyParams(): ?array
            {
                return $this->postdata;
            }
        };

        $votingMethods = new VotingMethods();
        $votingMethods->setRequestData($this->consultation, $request);
        $votingMethods->userVote($votingBlock, $user);

        return json_encode(['success' => true], JSON_THROW_ON_ERROR);
    }
}
