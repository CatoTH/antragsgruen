<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $consultationId
 * @property int|null $agendaItemId
 * @property int|null $motionId
 * @property int $quotaByTime
 * @property int $quotaOrder
 * @property int $isActive
 * @property int $isOpen
 * @property int $isModerated
 *
 * @property Consultation $consultation
 * @property ConsultationAgendaItem|null $agendaItem
 * @property Motion|null $motion
 * @property SpeechSubqueue[] $subqueues
 * @property SpeechQueueItem[] $items
 */
class SpeechQueue extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;

        return $app->tablePrefix . 'speechQueue';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }

    public function getMyConsultation(): Consultation
    {
        if (Consultation::getCurrent() && Consultation::getCurrent()->id === $this->consultationId) {
            return Consultation::getCurrent();
        } else {
            return $this->consultation;
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItem()
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'agendaItemId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSubqueues()
    {
        return $this->hasMany(SpeechSubqueue::class, ['queueId' => 'id'])->orderBy('position ASC');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getItems()
    {
        return $this->hasMany(SpeechQueueItem::class, ['queueId' => 'id']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'quotaByTime', 'quotaOrder', 'isOpen', 'isActive', 'isModerated'], 'required'],
            [['quotaByTime', 'quotaOrder', 'isOpen', 'isActive', 'isModerated'], 'safe'],
            [['id', 'consultationId', 'agendaItemId', 'quotaByTime', 'quotaOrder', 'isOpen', 'isActive', 'isModerated'], 'number'],
        ];
    }

    /**
     * @param string[] $names
     */
    public function setSubqueueConfiguration(array $names): void
    {
        if (count($names) > 1) {
            for ($i = 0; $i < count($this->subqueues); $i++) {
                if ($i < count($names)) {
                    $this->subqueues[$i]->name = $names[$i];
                    $this->subqueues[$i]->save();
                } else {
                    $this->subqueues[$i]->deleteReassignItems($this);
                }
            }
            // Create additional subqueues
            for ($i = count($this->subqueues); $i < count($names); $i++) {
                $subqueue           = new SpeechSubqueue();
                $subqueue->queueId  = $this->id;
                $subqueue->name     = $names[$i];
                $subqueue->position = $i;
                $subqueue->save();
            }
        } else {
            foreach ($this->subqueues as $subqueue) {
                $subqueue->deleteReassignItems($this);
            }
        }
    }

    public static function createWithSubqueues(Consultation $consultation): SpeechQueue
    {
        $queue                 = new SpeechQueue();
        $queue->consultationId = $consultation->id;
        $queue->motionId       = null;
        $queue->agendaItemId   = null;
        $queue->quotaByTime    = 0;
        $queue->quotaOrder     = 0;
        $queue->isActive       = 0;
        $queue->isOpen         = 0;
        $queue->isModerated    = 0;
        $queue->save();

        foreach ($consultation->getSettings()->speechListSubqueues as $i => $name) {
            $subqueue           = new SpeechSubqueue();
            $subqueue->queueId  = $queue->id;
            $subqueue->position = $i;
            $subqueue->name     = $name;
            $subqueue->save();
        }

        return $queue;
    }

    public function getSubqueueById(int $subqueueId): ?SpeechSubqueue
    {
        foreach ($this->subqueues as $subqueue) {
            if ($subqueue->id === $subqueueId) {
                return $subqueue;
            }
        }

        return null;
    }

    public function getItemById(int $itemId): ?SpeechQueueItem
    {
        foreach ($this->items as $item) {
            if ($item->id === $itemId) {
                return $item;
            }
        }

        return null;
    }

    private function getAdminApiSubqueue(?SpeechSubqueue $subqueue): array
    {
        $obj = [
            'id'      => ($subqueue ? $subqueue->id : null),
            'name'    => ($subqueue ? $subqueue->name : 'default'),
            'applied' => [],
            'onlist'  => [],
        ];

        foreach ($this->items as $item) {
            if (!(($subqueue && $subqueue->id === $item->subqueueId) || ($subqueue === null && $item->subqueueId === null))) {
                continue;
            }
            if ($item->position === null) {
                $obj['applied'][] = [
                    'id'        => $item->id,
                    'name'      => $item->name,
                    'userId'    => $item->userId,
                    'appliedAt' => $item->getDateApplied()->format('c'),
                ];
            } else {
                $obj['onlist'][] = [
                    'id'       => $item->id,
                    'name'     => $item->name,
                    'userId'   => $item->userId,
                    'position' => $item->position,
                ];
            }
        }

        return $obj;
    }

    private function getAdminApiSubqueues(): array
    {
        $subqueues = [];
        foreach ($this->subqueues as $subqueue) {
            $subqueues[] = $this->getAdminApiSubqueue($subqueue);
        }

        // Users without subqueue when there actually are existing subqueues:
        // this happens if a queue starts off without subqueues, someone registers,
        // and only afterwards subqueues are created. In this case, there will be a placeholder "default" queue.
        $usersWithoutSubqueue = 0;
        foreach ($this->items as $item) {
            if ($item->subqueueId === null && $item->position === null) {
                $usersWithoutSubqueue++;
            }
        }
        if (count($subqueues) === 0 || $usersWithoutSubqueue > 0) {
            $subqueues[] = $this->getAdminApiSubqueue(null);
        }

        return $subqueues;
    }

    private function getActiveSlots(): array
    {
        $slots = [];
        foreach ($this->items as $item) {
            if ($item->position === null) {
                continue;
            }
            $subqueue = ($item->subqueueId ? $this->getSubqueueById($item->subqueueId) : null);
            $slots[]  = [
                'id'          => $item->id,
                'subqueue'    => [
                    'id'   => ($subqueue ? $subqueue->id : null),
                    'name' => ($subqueue ? $subqueue->name : 'default'),
                ],
                'name'        => $item->name,
                'userId'      => $item->userId,
                'position'    => $item->position,
                'dateStarted' => ($item->getDateStarted() ? $item->getDateStarted()->format('c') : null),
                'dateStopped' => ($item->getDateStopped() ? $item->getDateStopped()->format('c') : null),
                'dateApplied' => ($item->getDateApplied() ? $item->getDateApplied()->format('c') : null),
            ];
        }
        usort($slots, function (array $entry1, array $entry2) {
            return $entry1['position'] <=> $entry2['position'];
        });

        return $slots;
    }

    public function getAdminApiObject(): array
    {
        return [
            'id'        => $this->id,
            'isActive'  => !!$this->isActive,
            'isOpen'    => !!$this->isOpen,
            'subqueues' => $this->getAdminApiSubqueues(),
            'slots'     => $this->getActiveSlots(),
        ];
    }

    private function getUserApiSubqueue(?SpeechSubqueue $subqueue): array
    {
        $user = User::getCurrentUser();

        $obj = [
            'id'         => ($subqueue ? $subqueue->id : null),
            'name'       => ($subqueue ? $subqueue->name : 'default'),
            'numApplied' => 0,
            'iAmOnList'  => false,
        ];

        foreach ($this->items as $item) {
            if (!(($subqueue && $subqueue->id === $item->subqueueId) || ($subqueue === null && $item->subqueueId === null))) {
                continue;
            }
            if ($item->position === null) {
                $obj['numApplied']++;
                if ($user && $item->userId && $user->id === $item->userId) {
                    $obj['iAmOnList'] = true;
                }
            }
        }

        return $obj;
    }

    private function getUserApiSubqueues(): array
    {
        $subqueues = [];
        foreach ($this->subqueues as $subqueue) {
            $subqueues[] = $this->getUserApiSubqueue($subqueue);
        }

        // Users without subqueue when there actually are existing subqueues:
        // this happens if a queue starts off without subqueues, someone registers,
        // and only afterwards subqueues are created. In this case, there will be a placeholder "default" queue.
        $usersWithoutSubqueue = 0;
        foreach ($this->items as $item) {
            if ($item->subqueueId === null && $item->position === null) {
                $usersWithoutSubqueue++;
            }
        }
        if (count($subqueues) === 0 || $usersWithoutSubqueue > 0) {
            $subqueues[] = $this->getUserApiSubqueue(null);
        }

        return $subqueues;
    }

    public function getUserApiObject(): array
    {
        $user = User::getCurrentUser();

        $iAmOnList = false;
        foreach ($this->items as $item) {
            if ($user && $item->userId && $user->id === $item->userId) {
                $iAmOnList = true;
            }
        }

        return [
            'id'        => $this->id,
            'iAmOnList' => $iAmOnList,
            'subqueues' => $this->getUserApiSubqueues(),
            'slots'     => $this->getActiveSlots(),
        ];
    }
}
