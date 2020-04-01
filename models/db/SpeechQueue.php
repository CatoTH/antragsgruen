<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $consultationId
 * @property int|null $agendaItemId
 * @property int $quotaByTime
 * @property int $quotaOrder
 * @property int $isActive
 * @property int $isOpen
 * @property int $isModerated
 *
 * @property Consultation $consultation
 * @property ConsultationAgendaItem|null $agendaItem
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
    public function getSubqueues()
    {
        return $this->hasMany(SpeechSubqueue::class, ['queueId' => 'id']);
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

    public function setSubqueueConfiguration(int $configuration): void
    {
        switch ($configuration) {
            case SpeechSubqueue::CONFIGURATION_NONE:
                foreach ($this->subqueues as $subqueue) {
                    $subqueue->deleteReassignItems($this);
                }
                break;
            case SpeechSubqueue::CONFIGURATION_GENDER:
                $hasMen   = false;
                $hasWomen = false;
                foreach ($this->subqueues as $subqueue) {
                    if ($subqueue->name === \Yii::t('speech', 'subqueue_female')) {
                        $hasWomen = true;
                    } elseif ($subqueue->name === \Yii::t('speech', 'subqueue_male')) {
                        $hasMen = true;
                    } else {
                        $subqueue->deleteReassignItems($this);
                    }
                }
                if (!$hasWomen) {
                    $subqueue           = new SpeechSubqueue();
                    $subqueue->queueId  = $this->id;
                    $subqueue->name     = \Yii::t('speech', 'subqueue_female');
                    $subqueue->position = 0;
                    $subqueue->save();
                }
                if (!$hasMen) {
                    $subqueue           = new SpeechSubqueue();
                    $subqueue->queueId  = $this->id;
                    $subqueue->name     = \Yii::t('speech', 'subqueue_male');
                    $subqueue->position = 1;
                    $subqueue->save();
                }
                break;
        }
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

    public function getAdminApiObject(): array
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

        return [
            'id'        => $this->id,
            'subqueues' => $this->getAdminApiSubqueues(),
            'slots'     => $slots,
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
            if ($item->subqueueId === null) {
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
        ];
    }
}
