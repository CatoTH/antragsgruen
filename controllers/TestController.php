<?php

namespace app\controllers;

use app\models\db\User;
use yii\web\Response;

class TestController extends Base
{
    public $enableCsrfValidation = false;

    public function actionIndex(string $action = '')
    {
        if (YII_ENV !== 'test') {
            die("Only accessible in testing mode");
        }
        if ($_SERVER['REMOTE_ADDR'] !== '::1' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
            die("Only accessible from localhost");
        }

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        switch ($action) {
            case 'set-amendment-status':
                return $this->actionSetAmendmentStatus();
            case 'set-user-fixed-data':
                return $this->actionSetUserFixedData();
        }

        return json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
    }

    /* Sample HTTP Request:

POST http://antragsgruen-test.local/stdparteitag/std-parteitag/test/set-amendment-status
Accept: application/json
Content-Type: application/x-www-form-urlencoded

id=270&status=3
     */
    private function actionSetAmendmentStatus()
    {
        $amendmentId = \Yii::$app->request->post('id');
        $status      = \Yii::$app->request->post('status');

        $amendment = $this->consultation->getAmendment($amendmentId);
        if (!$amendment) {
            return json_encode(['success' => false, 'error' => 'Amendment not found']);
        }

        $amendment->status = intval($status);
        $amendment->save();

        return json_encode(['success' => true]);
    }

    private function actionSetUserFixedData()
    {
        $user = User::findOne(['email' => \Yii::$app->request->post('email')]);
        if (!$user) {
            file_put_contents('/tmp/fixed.log', 'not found');
            return json_encode(['success' => false, 'message' => 'user not found']);
        }
        $user->fixedData = (\Yii::$app->request->post('fixed') ? 1 : 0);
        $user->nameFamily = \Yii::$app->request->post('nameFamily');
        $user->nameGiven = \Yii::$app->request->post('nameGiven');
        $user->name = \Yii::$app->request->post('nameGiven') . ' ' . \Yii::$app->request->post('nameFamily');
        $user->organization = \Yii::$app->request->post('organisation');
        $user->save();

        return json_encode(['success' => true]);
    }
}
