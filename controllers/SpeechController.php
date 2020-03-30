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

    public function actionAdminItemSetstatus()
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
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
        $item = $queue->getItemById(intval(\Yii::$app->request->post('item')));

        switch (\Yii::$app->request->post('op')) {
            case "set-slot":
                $maxPosition = 0;
                foreach ($queue->items as $cmpItem) {
                    if ($cmpItem->position !== null && $cmpItem->position > $maxPosition) {
                        $maxPosition = $cmpItem->position;
                    }
                }

                $item->position = $maxPosition + 1;
                $item->save();
                break;
            case "unset-slot":
                $item->position    = null;
                $item->dateStarted = null;
                $item->dateStopped = null;
                $item->save();
                break;
            case "start":
                $item->dateStarted = date("Y-m-d H:i:s");
                $item->dateStopped = null;
                $item->save();
                break;
            case "stop":
                $item->dateStopped = date("Y-m-d H:i:s");
                $item->save();
                break;
        }

        return json_encode([
            'success' => true,
            'queue'   => $queue->getAdminApiObject(),
        ]);
    }
}
