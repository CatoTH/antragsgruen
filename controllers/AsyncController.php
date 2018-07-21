<?php

namespace app\controllers;

use app\async\models\Motion;
use app\async\models\Userdata;
use app\models\db\User;
use yii\web\Response;

class AsyncController extends Base
{
    /**
     * @return string
     * @throws \Exception
     */
    public function actionUser()
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
        if (\Yii::$app->request->remoteIP !== '127.0.0.1' && \Yii::$app->request->remoteIP !== '::1') {
            \yii::$app->response->statusCode = 401;
            return json_encode(['error' => 'This IP is not whitelisted']);
        }
        $user = User::getCurrentUser();
        if (!$user) {
            \yii::$app->response->statusCode = 401;
            return json_encode(['error' => 'not logged in']);
        }
        return json_encode(Userdata::createFromDbObject($user));
    }

    /**
     * @return array
     */
    private function getObjectsMotions()
    {
        $data = [];
        foreach ($this->consultation->motions as $motion) {
            try {
                $data[] = Motion::createFromDbObject($motion);
            } catch (\Exception $e) {
            }
        }
        return $data;
    }

    /**
     * @param string $channel
     * @return string
     */
    public function actionObjects($channel)
    {
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
        if (\Yii::$app->request->remoteIP !== '127.0.0.1' && \Yii::$app->request->remoteIP !== '::1') {
            \yii::$app->response->statusCode = 401;
            return json_encode(['error' => 'This IP is not whitelisted']);
        }
        switch ($channel) {
            case 'motions':
                return json_encode($this->getObjectsMotions());
                break;
            default:
                return json_encode('unknown channel');
        }
    }

    /**
     * @return string
     * @throws \yii\base\ExitException
     */
    public function actionClient()
    {
        if (!User::getCurrentUser()) {
            return $this->showErrorpage(401, 'please log in');
        }
        return $this->render('client');
    }
}
