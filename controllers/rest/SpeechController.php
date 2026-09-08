<?php

namespace app\controllers\rest;

use app\components\{CookieUser, LiveTools, Tools};
use app\models\api\{SpeechUser, SpeechQueue as SpeechQueueApi};
use app\models\api\speech\{SpeechCreateItemRequest,
    SpeechItemOperationRequest,
    SpeechQueueAdmin,
    SpeechQueueSettingsRequest,
    SpeechQueueSettingsResponse,
    SpeechQueueUser,
    SpeechRegisterRequest};
use app\models\http\{RestApiExceptionResponse, RestApiResponse};
use app\models\settings\Privileges;
use app\views\speech\LayoutHelper;
use app\models\db\{SpeechQueue, SpeechQueueItem, User};
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;

class SpeechController extends RestBase
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

    public function actionGetQueue(string $queueIds): RestApiResponse
    {
        $this->handleRestHeaders(['GET'], true);

        $user       = User::getCurrentUser();
        $cookieUser = ($user ? null : CookieUser::getFromCookieOrCache());

        $response = [];

        $queueIds = array_map('intval', explode(',', $queueIds));
        foreach ($queueIds as $queueId) {
            $queue = $this->getQueue($queueId);
            if (!$queue) {
                return $this->returnRestResponseFromException(new \Exception('Queue not found'));
            }
            $response[] = SpeechQueueUser::fromEntity($queue, $user, $cookieUser);
        }

        return new RestApiResponse(200, null, Tools::getSerializer()->serialize($response, 'json'));
    }

    public function actionRegister(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);

        try {
            /** @var SpeechRegisterRequest $request */
            $request = Tools::getSerializer()->deserialize($this->getPostBody(), SpeechRegisterRequest::class, 'json');
        } catch (SerializerException $e) {
            return new RestApiExceptionResponse(400, 'Invalid request body: ' . $e->getMessage());
        }

        $user = User::getCurrentUser();
        if (!$user) {
            if ($this->consultation->getSettings()->speechRequiresLogin) {
                return new RestApiExceptionResponse(401, 'Not logged in');
            } elseif ($request->username) {
                $cookieUser = CookieUser::getFromCookieOrCreate($request->username);
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
            $subqueue = ($request->subqueue !== null ? $queue->getSubqueueById($request->subqueue) : null);
            if (!$subqueue) {
                return new RestApiExceptionResponse(400, 'No subqueue provided');
            }
        } else {
            $subqueue = null;
        }

        if ($user && !$queue->getSettings()->allowCustomNames) {
            $name = SpeechUser::getFormattedUserName($user);
        } elseif ($request->username) {
            $name = trim($request->username);
        } else {
            $name = SpeechUser::getFormattedUserName($user);
        }

        $pointOfOrder = ($request->pointOfOrder === true);
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

        LiveTools::sendSpeechQueue($this->consultation, SpeechQueueApi::fromEntity($queue));

        return $this->createResponse(200, SpeechQueueUser::fromEntity($queue, $user, $cookieUser));
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

        LiveTools::sendSpeechQueue($this->consultation, SpeechQueueApi::fromEntity($queue));

        return $this->createResponse(200, SpeechQueueUser::fromEntity($queue, $user, $cookieUser));
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

    public function actionGetQueueAdmin(string $queueIds): RestApiResponse
    {
        $this->handleRestHeaders(['GET'], true);

        $response = [];
        try {
            foreach (explode(',', $queueIds) as $queueId) {
                $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
                $response[] = SpeechQueueAdmin::fromEntity($queue);
            }
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        return new RestApiResponse(200, null, Tools::getSerializer()->serialize($response, 'json'));
    }

    public function actionPostQueueSettings(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        try {
            /** @var SpeechQueueSettingsRequest $request */
            $request = Tools::getSerializer()->deserialize($this->getPostBody(), SpeechQueueSettingsRequest::class, 'json');
        } catch (SerializerException $e) {
            return new RestApiExceptionResponse(400, 'Invalid request body: ' . $e->getMessage());
        }

        $settings = $queue->getSettings();
        $settings->isOpen = $request->isOpen;
        $settings->isOpenPoo = $request->isOpenPoo;
        $settings->allowCustomNames = $request->allowCustomNames;
        $settings->preferNonspeaker = $request->preferNonspeaker;
        $settings->showNames = $request->showNames;
        $settings->speakingTime = (($request->speakingTime !== null && $request->speakingTime > 0) ? $request->speakingTime : null);
        $queue->setSettings($settings);

        $queue->isActive = ($request->isActive ? 1 : 0);
        $queue->save();

        if ($queue->isActive) {
            $settings = $this->consultation->getSettings();
            if (!$settings->hasSpeechLists) {
                $settings->hasSpeechLists = true;
                $this->consultation->setSettings($settings);
                $this->consultation->save();
            }
        }
        if ($queue->isActive && $queue->agendaItemId === null) {
            foreach ($this->consultation->speechQueues as $otherQueue) {
                if ($otherQueue->id !== $queue->id && $otherQueue->agendaItemId === null) {
                    $otherQueue->isActive = 0;
                    $otherQueue->save();
                }
            }
        }

        LiveTools::sendSpeechQueue($this->consultation, SpeechQueueApi::fromEntity($queue));

        $response = new SpeechQueueSettingsResponse(
            queue: SpeechQueueAdmin::fromEntity($queue),
            sidebar: LayoutHelper::getSidebars($this->consultation, $queue),
        );
        return $this->createResponse(200, $response);
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

        LiveTools::sendSpeechQueue($this->consultation, SpeechQueueApi::fromEntity($queue));

        return $this->createResponse(200, SpeechQueueAdmin::fromEntity($queue));
    }

    public function actionAdminQueueRandomize(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        $queue->randomizeWaitingList();
        $queue->refresh();

        LiveTools::sendSpeechQueue($this->consultation, SpeechQueueApi::fromEntity($queue));

        return $this->createResponse(200, SpeechQueueAdmin::fromEntity($queue));
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
                try {
                    /** @var SpeechItemOperationRequest $request */
                    $request = Tools::getSerializer()->deserialize($this->getPostBody() ?: '{}', SpeechItemOperationRequest::class, 'json');
                } catch (SerializerException $e) {
                    return new RestApiExceptionResponse(400, 'Invalid request body: ' . $e->getMessage());
                }
                $newPosition = (int)($request->position ?? 0);
                if ($request->newSubqueueId !== null && $request->newSubqueueId > 0) {
                    $subqueue         = $queue->getSubqueueById($request->newSubqueueId);
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

        LiveTools::sendSpeechQueue($this->consultation, SpeechQueueApi::fromEntity($queue));

        return $this->createResponse(200, SpeechQueueAdmin::fromEntity($queue));
    }

    public function actionAdminCreateItem(string $queueId): RestApiResponse
    {
        $this->handleRestHeaders(['POST'], true);
        try {
            $queue = $this->getQueueAndCheckMethodAndPermission($queueId);
        } catch (\Exception $e) {
            return $this->returnRestResponseFromException($e);
        }

        try {
            /** @var SpeechCreateItemRequest $request */
            $request = Tools::getSerializer()->deserialize($this->getPostBody(), SpeechCreateItemRequest::class, 'json');
        } catch (SerializerException $e) {
            return new RestApiExceptionResponse(400, 'Invalid request body: ' . $e->getMessage());
        }

        if ($request->subqueue !== null && $request->subqueue > 0) {
            $subqueue = $queue->getSubqueueById($request->subqueue);
        } else {
            $subqueue = null;
        }
        if (count($queue->subqueues) > 0 && !$subqueue) {
            return new RestApiExceptionResponse(400, 'No subqueue given');
        }

        $name = trim($request->name);
        if (!$name) {
            return new RestApiExceptionResponse(400, 'No name entered');
        }

        $queue->createItemOnAppliedList($name, $subqueue, null, null, false);

        LiveTools::sendSpeechQueue($this->consultation, SpeechQueueApi::fromEntity($queue));

        return $this->createResponse(200, SpeechQueueAdmin::fromEntity($queue));
    }
}
