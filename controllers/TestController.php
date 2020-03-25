<?php

namespace app\controllers;

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
        }

        return json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
    }

    /*
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
}
