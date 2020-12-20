<?php

namespace app\controllers;

use app\components\CookieUser;
use app\views\speech\LayoutHelper;
use app\models\db\{SpeechQueue, SpeechQueueItem, User};
use yii\web\Response;

class SpeechController extends Base
{
    private function getError(string $message): string
    {
        return json_encode([
            'success' => false,
            'message' => $message,
        ]);
    }

    private function getQueue(int $queueId): ?SpeechQueue
    {
        foreach ($this->consultation->speechQueues as $queue) {
            if ($queue->id === $queueId) {
                return $queue;
            }
        }

        return null;
    }

    public function actionGetQueue(string $queueId)
    {
        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user       = User::getCurrentUser();
        $cookieUser = ($user ? null : CookieUser::getFromCookieOrCache());

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            return $this->returnRestResponse(404, $this->getError('Queue not found'));
        }

        $responseJson = json_encode($queue->getUserApiObject($user, $cookieUser));
        return $this->returnRestResponse(200, $responseJson);
    }

    public function actionRegister()
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user) {
            if ($this->consultation->getSettings()->speechRequiresLogin) {
                return $this->getError('Not logged in');
            } elseif (\Yii::$app->request->post('username')) {
                $cookieUser = CookieUser::getFromCookieOrCreate(\Yii::$app->request->post('username'));
            } else {
                return $this->getError('No name provided');
            }
        } else {
            $cookieUser = null;
        }

        $queue = $this->getQueue(intval(\Yii::$app->request->post('queue')));
        if (!$queue) {
            return $this->getError('Queue not found');
        }
        if (!$queue->getSettings()->isOpen) {
            return $this->getError(\Yii::t('speech', 'err_permission_apply'));
        }
        if (count($queue->subqueues) > 0) {
            // Providing a subqueue is necessary if there are some; otherwise, it goes into the "default" subqueue
            $subqueue = $queue->getSubqueueById(intval(\Yii::$app->request->post('subqueue')));
            if ($subqueue) {
                $subqueueId = $subqueue->id;
            } else {
                return $this->getError('No subqueue provided');
            }
        } else {
            $subqueueId = null;
        }
        if (\Yii::$app->request->post('username')) {
            $name = trim(\Yii::$app->request->post('username'));
        } else {
            $name = $user->name;
        }

        $item              = new SpeechQueueItem();
        $item->queueId     = $queue->id;
        $item->subqueueId  = $subqueueId;
        $item->userId      = ($user ? $user->id : null);
        $item->userToken   = ($cookieUser ? $cookieUser->userToken : null);
        $item->name        = $name;
        $item->position    = null;
        $item->dateApplied = date('Y-m-d H:i:s');
        $item->dateStarted = null;
        $item->dateStopped = null;
        $item->save();

        return json_encode([
            'success' => true,
            'queue'   => $queue->getUserApiObject($user, $cookieUser),
        ]);
    }

    public function actionUnregister()
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        $cookieUser = CookieUser::getFromCookieOrCache();

        $queue = $this->getQueue(intval(\Yii::$app->request->post('queue')));
        if (!$queue) {
            return $this->getError('Queue not found');
        }

        foreach ($queue->items as $item) {
            if ($item->dateStarted) {
                // One can only delete oneself before the speech has started
                continue;
            }
            if (($user && $item->userId === $user->id) || ($cookieUser && $cookieUser->userToken && $item->userToken === $cookieUser->userToken)) {
                /** @noinspection PhpUnhandledExceptionInspection */
                $item->delete();
            }
        }
        $queue->refresh();

        return json_encode([
            'success' => true,
            'queue'   => $queue->getUserApiObject($user, $cookieUser),
        ]);
    }


    public function actionGetQueueAdmin(string $queueId)
    {
        $this->handleRestHeaders(['GET'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user->hasPrivilege($this->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            return $this->returnRestResponse(403, $this->getError('Missing privileges'));
        }

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            return $this->returnRestResponse(404, $this->getError('Queue not found'));
        }

        $jsonResponse = json_encode($queue->getAdminApiObject());
        return $this->returnRestResponse(200, $jsonResponse);
    }

    public function actionPostQueueSettings(string $queueId)
    {
        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user->hasPrivilege($this->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            return $this->returnRestResponse(403, $this->getError('Missing privileges'));
        }

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            return $this->returnRestResponse(404, $this->getError('Queue not found'));
        }

        $settings                   = $queue->getSettings();
        $settings->isOpen           = (intval(\Yii::$app->request->post('is_open')) > 0);
        $settings->preferNonspeaker = (intval(\Yii::$app->request->post('prefer_nonspeaker')) > 0);
        $queue->setSettings($settings);

        $queue->isActive = (intval(\Yii::$app->request->post('is_active')) > 0 ? 1 : 0);
        $queue->save();

        if ($queue->isActive) {
            foreach ($this->consultation->speechQueues as $otherQueue) {
                if ($otherQueue->id !== $queue->id) {
                    $otherQueue->isActive = 0;
                    $otherQueue->save();
                }
            }
        }

        $jsonResponse = json_encode([
            'queue'   => $queue->getAdminApiObject(),
            'sidebar' => LayoutHelper::getSidebars($this->consultation, $queue),
        ]);
        return $this->returnRestResponse(200, $jsonResponse);
    }

    public function actionAdminItemSetstatus()
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user->hasPrivilege($this->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            return $this->getError('Missing privileges');
        }

        $queue = $this->getQueue(intval(\Yii::$app->request->post('queue')));
        if (!$queue) {
            return $this->getError('Queue not found');
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

                $item->position    = $maxPosition + 1;
                $item->dateStarted = null;
                $item->dateStopped = null;
                $item->save();
                break;
            case "unset-slot":
                $item->position    = null;
                $item->dateStarted = null;
                $item->dateStopped = null;
                $item->save();
                break;
            case "set-slot-and-start":
                $maxPosition = 0;
                foreach ($queue->items as $cmpItem) {
                    if ($cmpItem->position !== null && $cmpItem->position > $maxPosition) {
                        $maxPosition = $cmpItem->position;
                    }
                }

                $item->position    = $maxPosition + 1;
                $item->dateStarted = date("Y-m-d H:i:s");
                $item->dateStopped = null;
                $item->save();

                foreach ($queue->items as $cmpItem) {
                    if ($cmpItem->id !== $item->id && $cmpItem->dateStarted !== null && $cmpItem->dateStopped === null) {
                        $cmpItem->dateStopped = date("Y-m-d H:i:s");
                        $cmpItem->save();
                    }
                }
                break;
            case "start":
                $item->dateStarted = date("Y-m-d H:i:s");
                $item->dateStopped = null;
                $item->save();

                foreach ($queue->items as $cmpItem) {
                    if ($cmpItem->id !== $item->id && $cmpItem->dateStarted !== null && $cmpItem->dateStopped === null) {
                        $cmpItem->dateStopped = date("Y-m-d H:i:s");
                        $cmpItem->save();
                    }
                }
                break;
            case "stop":
                $item->dateStopped = date("Y-m-d H:i:s");
                $item->save();
                break;
            case "move":
                if (\Yii::$app->request->post('newSubqueueId')) {
                    $subqueue         = $queue->getSubqueueById(intval(\Yii::$app->request->post('newSubqueueId')));
                    $item->subqueueId = $subqueue->id;
                } else {
                    $item->subqueueId = null;
                }
                $item->save();
                break;
        }

        return json_encode([
            'success' => true,
            'queue'   => $queue->getAdminApiObject(),
        ]);
    }

    public function actionAdminCreateItem()
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user->hasPrivilege($this->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            return $this->getError('Missing privileges');
        }

        $queue = $this->getQueue(intval(\Yii::$app->request->post('queue')));
        if (!$queue) {
            return $this->getError('Queue not found');
        }

        if (\Yii::$app->request->post('subqueue')) {
            $subqueue = $queue->getSubqueueById(intval(\Yii::$app->request->post('subqueue')));
        } else {
            $subqueue = null;
        }
        if (count($queue->subqueues) > 0 && !$subqueue) {
            return $this->getError('No subqueue given');
        }

        $name = trim(\Yii::$app->request->post('name'));
        if (!$name) {
            return $this->getError('No name entered');
        }

        $item              = new SpeechQueueItem();
        $item->queueId     = $queue->id;
        $item->subqueueId  = ($subqueue ? $subqueue->id : null);
        $item->userId      = null;
        $item->name        = $name;
        $item->position    = null;
        $item->dateApplied = date('Y-m-d H:i:s');
        $item->save();

        return json_encode([
            'success' => true,
            'queue'   => $queue->getAdminApiObject(),
        ]);
    }
}
