<?php

namespace app\controllers;

use app\models\db\{SpeechQueue, SpeechQueueItem, User};
use yii\web\Response;

class SpeechController extends Base
{
    private function getQueue(int $queueId): ?SpeechQueue
    {
        foreach ($this->consultation->speechQueues as $queue) {
            if ($queue->id === $queueId) {
                return $queue;
            }
        }

        return null;
    }

    public function actionRegister()
    {
        $user  = User::getCurrentUser();
        $queue = $this->getQueue(intval(\Yii::$app->request->post('queue')));
        if (!$queue) {
            return json_encode([
                'success' => false,
                'message' => 'Queue not found',
            ]);
        }

        $item             = new SpeechQueueItem();
        $item->queueId    = $queue->id;
        $item->subqueueId = null;
        $item->userId     = $user->id;
        $item->name       = $user->name;
        $item->position   = 0;
        $item->save();

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        return json_encode([
            'success' => true,
            'queue'   => $queue->getUserObject(),
        ]);
    }
}
