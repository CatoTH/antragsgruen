<?php

namespace app\controllers;

use app\components\CookieUser;
use app\components\LiveTools;
use app\models\api\{SpeechUser, SpeechQueue as SpeechQueueApi};
use app\models\http\{RestApiExceptionResponse, RestApiResponse};
use app\models\settings\Privileges;
use app\views\speech\LayoutHelper;
use app\models\db\{SpeechQueue, SpeechQueueItem, User};

class SpeechController extends Base
{
    public const VIEW_ID_GET_QUEUE = 'get-queue';

    // *** Shared methods ***

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

    public function actionGetQueue(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['GET'], true);

        $user       = User::getCurrentUser();
        $cookieUser = ($user ? null : CookieUser::getFromCookieOrCache());

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            return $this->returnRestResponseFromException(new \Exception('Queue not found'));
        }

        return new RestApiResponse(200, SpeechQueueApi::fromEntity($queue)->toUserApi($user, $cookieUser));
    }

    public function actionRegister(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);

        $user = User::getCurrentUser();
        if (!$user) {
            if ($this->consultation->getSettings()->speechRequiresLogin) {
                return new RestApiExceptionResponse(401, 'Not logged in');
            } elseif ($this->getHttpRequest()->post('username')) {
                $cookieUser = CookieUser::getFromCookieOrCreate($this->getHttpRequest()->post('username'));
            } else {
                return new RestApiExceptionResponse(400, 'No name provided');
            }
        } else {
            $cookieUser = null;
        }

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            return new RestApiExceptionResponse(404, 'Queue not found');
        }
        if (count($queue->subqueues) > 0) {
            // Providing a subqueue is necessary if there are some; otherwise, it goes into the "default" subqueue
            $subqueue = $queue->getSubqueueById(intval($this->getHttpRequest()->post('subqueue')));
            if (!$subqueue) {
                return new RestApiExceptionResponse(400, 'No subqueue provided');
            }
        } else {
            $subqueue = null;
        }

        if ($user && !$queue->getSettings()->allowCustomNames) {
            $name = SpeechUser::getFormattedUserName($user);
        } elseif ($this->getHttpRequest()->post('username')) {
            $name = trim($this->getHttpRequest()->post('username'));
        } else {
            $name = SpeechUser::getFormattedUserName($user);
        }

        $pointOfOrder = ($this->getHttpRequest()->post('pointOfOrder') > 0);
        if ($pointOfOrder) {
            if (!$queue->getSettings()->isOpenPoo) {
                return new RestApiExceptionResponse(403, \Yii::t('speech', 'err_permission_apply'));
            }
        } else {
            if (!$queue->getSettings()->isOpen) {
                return new RestApiExceptionResponse(403, \Yii::t('speech', 'err_permission_apply'));
            }
        }

        $queue->createItemOnAppliedList($name, $subqueue, $user, $cookieUser, $pointOfOrder);

        $apiDto = SpeechQueueApi::fromEntity($queue);
        LiveTools::sendSpeechQueue($this->consultation, $apiDto);

        return new RestApiResponse(200, $apiDto->toUserApi($user, $cookieUser));
    }

    public function actionUnregister(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);

        $user = User::getCurrentUser();
        $cookieUser = CookieUser::getFromCookieOrCache();

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            return new RestApiExceptionResponse(404, 'Queue not found');
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

        $apiDto = SpeechQueueApi::fromEntity($queue);
        LiveTools::sendSpeechQueue($this->consultation, $apiDto);

        return new RestApiResponse(200, $apiDto->toUserApi($user, $cookieUser));
    }

    // *** Admin-facing methods ***

    private function getQueueAndCheckMethodAndPermission(string $queueId): SpeechQueue
    {
        $user = User::getCurrentUser();
        if (!$user || !$user->hasPrivilege($this->consultation, Privileges::PRIVILEGE_SPEECH_QUEUES, null)) {
            throw new \Exception('Missing privileges');
        }

        $queue = $this->getQueue(intval($queueId));
        if (!$queue) {
            throw new \Exception('Queue not found');
        }

        return $queue;
    }

    public function actionGetQueueAdmin(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['GET'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        return new RestApiResponse(200, SpeechQueueApi::fromEntity($queue)->getAdminApiObject());
    }

    public function actionPostQueueSettings(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        $settings = $queue->getSettings();
        $settings->isOpen = ($this->getHttpRequest()->post('is_open') > 0);
        $settings->isOpenPoo = ($this->getHttpRequest()->post('is_open_poo') > 0);
        $settings->allowCustomNames = ($this->getHttpRequest()->post('allow_custom_names') > 0);
        $settings->preferNonspeaker = (intval($this->getHttpRequest()->post('prefer_nonspeaker')) > 0);
        $settings->showNames = (intval($this->getHttpRequest()->post('show_names')) > 0);
        if ($this->getHttpRequest()->post('speaking_time') > 0) {
            $settings->speakingTime = intval($this->getHttpRequest()->post('speaking_time'));
        } else {
            $settings->speakingTime = null;
        }
        $queue->setSettings($settings);

        $queue->isActive = ($this->getHttpRequest()->post('is_active') > 0 ? 1 : 0);
        $queue->save();

        if ($queue->isActive) {
            $settings = $this->consultation->getSettings();
            if (!$settings->hasSpeechLists) {
                $settings->hasSpeechLists = true;
                $this->consultation->setSettings($settings);
                $this->consultation->save();
            }

            foreach ($this->consultation->speechQueues as $otherQueue) {
                if ($otherQueue->id !== $queue->id) {
                    $otherQueue->isActive = 0;
                    $otherQueue->save();
                }
            }
        }

        $apiDto = SpeechQueueApi::fromEntity($queue);
        LiveTools::sendSpeechQueue($this->consultation, $apiDto);

        $jsonResponse = [
            'queue'   => $apiDto->getAdminApiObject(),
            'sidebar' => LayoutHelper::getSidebars($this->consultation, $queue),
        ];
        return new RestApiResponse(200, $jsonResponse);
    }

    public function actionAdminQueueReset(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        foreach ($queue->items as $item) {
            $item->delete();
        }

        $queue->refresh();

        $apiDto = SpeechQueueApi::fromEntity($queue);
        LiveTools::sendSpeechQueue($this->consultation, $apiDto);

        return new RestApiResponse(200, $apiDto->getAdminApiObject());
    }

    /**
     * @param SpeechQueueItem[] $items
     */
    private function moveAppliedItemsDownStartingPosition(array $items, int $position, ?int $excludeItemId = null): void
    {
        $applied = array_values(array_filter($items, function (SpeechQueueItem $item) use ($excludeItemId) {
            return $item->position < 0 && $item->id !== $excludeItemId;
        }));
        foreach ($applied as $pos => $otherItem) {
            if ($pos < $position) {
                $otherItem->position = -1 * $pos - 1;
            } else {
                $otherItem->position = -1 * $pos - 2;
            }
            $otherItem->save();
        }
    }

    public function actionPostItemOperation(string $queueId, string $itemId, string $op): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        $item = $queue->getItemById(intval($itemId));
        if (!$item) {
            return new RestApiExceptionResponse(404, 'Item not found');
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
                $subqueue = $item->subqueueId ? $queue->getSubqueueById($item->subqueueId) : null;
                $this->moveAppliedItemsDownStartingPosition($queue->getSortedItems($subqueue), 0, $item->id);

                $item->position    = -1;
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
                $queue->startItem($item);
                break;
            case "start":
                $queue->startItem($item);
                break;
            case "stop":
                $item->dateStopped = date("Y-m-d H:i:s");
                $item->save();
                break;
            case "move":
                $newPosition = $this->getHttpRequest()->post('position');
                if ($this->getHttpRequest()->post('newSubqueueId') > 0) {
                    $subqueue         = $queue->getSubqueueById(intval($this->getHttpRequest()->post('newSubqueueId')));
                    $item->subqueueId = $subqueue->id;
                } else {
                    $subqueue = null;
                    $item->subqueueId = null;
                }
                $this->moveAppliedItemsDownStartingPosition($queue->getSortedItems($subqueue), $newPosition, $item->id);

                $item->position = -1 * $newPosition - 1;
                $item->save();
                break;
            case "delete":
                $item->delete();
                break;
        }

        $queue->refresh();

        $apiDto = SpeechQueueApi::fromEntity($queue);
        LiveTools::sendSpeechQueue($this->consultation, $apiDto);

        return new RestApiResponse(200, $apiDto->getAdminApiObject());
    }

    public function actionAdminCreateItem(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        if ($this->getHttpRequest()->post('subqueue')) {
            $subqueue = $queue->getSubqueueById(intval($this->getHttpRequest()->post('subqueue')));
        } else {
            $subqueue = null;
        }
        if (count($queue->subqueues) > 0 && !$subqueue) {
            return new RestApiExceptionResponse(400, 'No subqueue given');
        }

        $name = trim($this->getHttpRequest()->post('name'));
        if (!$name) {
            return new RestApiExceptionResponse(400, 'No name entered');
        }

        $queue->createItemOnAppliedList($name, $subqueue, null, null, false);

        $apiDto = SpeechQueueApi::fromEntity($queue);
        LiveTools::sendSpeechQueue($this->consultation, $apiDto);

        return new RestApiResponse(200, $apiDto->getAdminApiObject());
    }
}
