<?php

namespace app\controllers;

use app\components\CookieUser;
use app\views\speech\LayoutHelper;
use app\models\db\{SpeechQueue, User};
use yii\web\Response;

class SpeechController extends Base
{
    // *** Shared methods ***

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

    // *** User-facing methods ***

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

    public function actionRegister(string $queueId)
    {
        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user) {
            if ($this->consultation->getSettings()->speechRequiresLogin) {
                return $this->returnRestResponse(401, $this->getError('Not logged in'));
            } elseif (\Yii::$app->request->post('username')) {
                $cookieUser = CookieUser::getFromCookieOrCreate(\Yii::$app->request->post('username'));
            } else {
                return $this->returnRestResponse(400, $this->getError('No name provided'));
            }
        } else {
            $cookieUser = null;
        }

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            $this->returnRestResponse(404, $this->getError('Queue not found'));
        }
        if (!$queue->getSettings()->isOpen) {
            return $this->returnRestResponse(403, $this->getError(\Yii::t('speech', 'err_permission_apply')));
        }
        if (count($queue->subqueues) > 0) {
            // Providing a subqueue is necessary if there are some; otherwise, it goes into the "default" subqueue
            $subqueue = $queue->getSubqueueById(intval(\Yii::$app->request->post('subqueue')));
            if (!$subqueue) {
                return $this->returnRestResponse(400, $this->getError('No subqueue provided'));
            }
        } else {
            $subqueue = null;
        }

        if (\Yii::$app->request->post('username')) {
            $name = trim(\Yii::$app->request->post('username'));
        } else {
            $name = $user->name;
        }

        $queue->createItemOnAppliedList($name, $subqueue, $user, $cookieUser);

        $responseJson = json_encode($queue->getUserApiObject($user, $cookieUser));
        return $this->returnRestResponse(200, $responseJson);
    }

    public function actionUnregister(string $queueId)
    {
        $this->handleRestHeaders(['POST'], true);

        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        $cookieUser = CookieUser::getFromCookieOrCache();

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            return $this->returnRestResponse(404, $this->getError('Queue not found'));
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

        $responseJson = json_encode($queue->getUserApiObject($user, $cookieUser));
        return $this->returnRestResponse(200, $responseJson);
    }

    // *** Admin-facing methods ***

    private function getQueueAndCheckMethodAndPermission(string $queueId): SpeechQueue
    {
        \Yii::$app->response->format = Response::FORMAT_RAW;
        \Yii::$app->response->headers->add('Content-Type', 'application/json');

        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, User::PRIVILEGE_SPEECH_QUEUES)) {
            $this->returnRestResponse(403, $this->getError('Missing privileges'));
            die();
        }

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            $this->returnRestResponse(404, $this->getError('Queue not found'));
            die();
        }

        return $queue;
    }

    public function actionGetQueueAdmin(string $queueId)
    {
        $this->handleRestHeaders(['GET'], true);
        $queue = $this->getQueueAndCheckMethodAndPermission($queueId);

        $jsonResponse = json_encode($queue->getAdminApiObject());
        return $this->returnRestResponse(200, $jsonResponse);
    }

    public function actionPostQueueSettings(string $queueId)
    {
        $this->handleRestHeaders(['POST'], true);
        $queue = $this->getQueueAndCheckMethodAndPermission($queueId);

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

    public function actionAdminQueueReset(string $queueId)
    {
        $this->handleRestHeaders(['POST'], true);
        $queue = $this->getQueueAndCheckMethodAndPermission($queueId);

        foreach ($queue->items as $item) {
            $item->delete();
        }

        $queue->refresh();
        $jsonResponse = json_encode($queue->getAdminApiObject());
        return $this->returnRestResponse(200, $jsonResponse);
    }

    public function actionPostItemOperation(string $queueId, string $itemId, string $op)
    {
        $this->handleRestHeaders(['POST'], true);
        $queue = $this->getQueueAndCheckMethodAndPermission($queueId);

        $item = $queue->getItemById(intval($itemId));
        if (!$item) {
            return $this->returnRestResponse(404, $this->getError('Item not found'));
        }

        switch ($op) {
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
                $newPosition = \Yii::$app->request->post('position');
                if (\Yii::$app->request->post('newSubqueueId')) {
                    $subqueue         = $queue->getSubqueueById(intval(\Yii::$app->request->post('newSubqueueId')));
                    $item->subqueueId = $subqueue->id;
                } else {
                    $subqueue = null;
                    $item->subqueueId = null;
                }

                foreach ($queue->getAppliedItems($subqueue) as $pos => $otherItem) {
                    if ($otherItem->id === $item->id) {
                        continue;
                    }
                    if ($pos < $newPosition) {
                        $otherItem->position = -1 * $pos - 1;
                    } else {
                        $otherItem->position = -1 * $pos - 2;
                    }
                    $otherItem->save();
                }

                $item->position = -1 * $newPosition - 1;
                $item->save();
                break;
        }

        $responseJson = json_encode($queue->getAdminApiObject());
        return $this->returnRestResponse(200, $responseJson);
    }

    public function actionAdminCreateItem(string $queueId)
    {
        $this->handleRestHeaders(['POST'], true);
        $queue = $this->getQueueAndCheckMethodAndPermission($queueId);

        if (\Yii::$app->request->post('subqueue')) {
            $subqueue = $queue->getSubqueueById(intval(\Yii::$app->request->post('subqueue')));
        } else {
            $subqueue = null;
        }
        if (count($queue->subqueues) > 0 && !$subqueue) {
            return $this->returnRestResponse(400, $this->getError('No subqueue given'));
        }

        $name = trim(\Yii::$app->request->post('name'));
        if (!$name) {
            return $this->returnRestResponse(400, $this->getError('No name entered'));
        }

        $queue->createItemOnAppliedList($name, $subqueue, null, null);

        $responseJson = json_encode($queue->getAdminApiObject());
        return $this->returnRestResponse(200, $responseJson);
    }
}
