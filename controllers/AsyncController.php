<?php

namespace app\controllers;

use app\async\models\TransferrableChannelObject;
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
        return json_encode(Userdata::createFromDbObject($user, $this->consultation));
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
        $class = TransferrableChannelObject::$CHANNEL_CLASSES[$channel];
        return json_encode($class::getCollection($this->consultation));
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
