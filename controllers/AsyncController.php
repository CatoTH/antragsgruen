<?php

namespace app\controllers;

use app\async\models\Motion;
use app\async\models\Userdata;
use app\models\db\User;
use yii\web\Response;
use yii\web\UnauthorizedHttpException;

class AsyncController extends Base
{
    /**
     * @return string
     * @throws UnauthorizedHttpException
     */
    public function actionUser()
    {
        if (\Yii::$app->request->remoteIP !== '127.0.0.1' && \Yii::$app->request->remoteIP !== '::1') {
            throw new UnauthorizedHttpException('This IP is not whitelisted');
        }
        $user = User::getCurrentUser();
        if (!$user) {
            throw new UnauthorizedHttpException('not logged in', 401);
        }
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
        return Userdata::createFromDbObject($user)->toJSON();
    }

    /**
     * @return array
     */
    private function getObjectsMotions()
    {
        $data = [];
        foreach ($this->consultation->motions as $motion) {
            try {
                $data[] = Motion::createFromDbObject($motion)->toJSONdata();
            } catch (\Exception $e) {
            }
        }
        return $data;
    }

    /**
     * @param string $channel
     * @return string
     * @throws UnauthorizedHttpException
     */
    public function actionObjects($channel)
    {
        if (\Yii::$app->request->remoteIP !== '127.0.0.1' && \Yii::$app->request->remoteIP !== '::1') {
            throw new UnauthorizedHttpException('This IP is not whitelisted');
        }
        \yii::$app->response->format = Response::FORMAT_RAW;
        \yii::$app->response->headers->add('Content-Type', 'application/json');
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
     */
    public function actionClient()
    {
        return $this->render('client');
    }
}
