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
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user  = User::getCurrentUser();
        $queue = $this->getQueue(intval(\Yii::$app->request->post('queue')));
        if (!$queue) {
            return json_encode([
                'success' => false,
                'message' => 'Queue not found',
            ]);
        }

        $item              = new SpeechQueueItem();
        $item->queueId     = $queue->id;
        $item->subqueueId  = null;
        $item->userId      = $user->id;
        $item->name        = $user->name;
        $item->position    = 0;
        $item->dateApplied = date('Y-m-d H:i:s');
        $item->save();

        return json_encode([
            'success' => true,
            'queue'   => $queue->getUserApiObject(),
        ]);
    }

    public function actionAdminItemSetposition()
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user  = User::getCurrentUser();
        if (!$user->hasPrivilege($this->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            return json_encode([
                'success' => false,
                'message' => 'Missing privileges',
            ]);
        }

        $queue = $this->getQueue(intval(\Yii::$app->request->post('queue')));
        if (!$queue) {
            return json_encode([
                'success' => false,
                'message' => 'Queue not found',
            ]);
        }
        if (\Yii::$app->request->post('subqueue')) {
            $subqueue = $queue->getSubqueueById(intval(\Yii::$app->request->post('subqueue')));
        } else {
            $subqueue = null;
        }
        $item = $queue->getItemById(intval(\Yii::$app->request->post('item')));

        if (\Yii::$app->request->post('position') === "max") {
            $maxPosition = 0;
            foreach ($queue->items as $item) {
                if ($item->position !== null && $item->position > $maxPosition) {
                    $maxPosition = $item->position;
                }
            }

            $item->position = $maxPosition + 1;
            $item->save();
        }

        return json_encode([
            'success' => true,
            'queue'   => $queue->getAdminApiObject(),
        ]);
    }
}
