<?php

namespace app\models\db;

use app\components\{CookieUser, UrlHelper};
use app\models\settings\{AntragsgruenApp, SpeechQueue as SpeechQueueSettings};
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int $id
 * @property int $consultationId
 * @property int|null $agendaItemId
 * @property int|null $motionId
 * @property int $isActive
 * @property string|null $settings
 *
 * @property Consultation $consultation
 * @property ConsultationAgendaItem|null $agendaItem
 * @property Motion|null $motion
 * @property SpeechSubqueue[] $subqueues
 * @property SpeechQueueItem[] $items
 */
class SpeechQueue extends ActiveRecord
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'speechQueue';
    }

    public function getConsultation(): ActiveQuery
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

    public function getAgendaItem(): ActiveQuery
    {
        return $this->hasOne(ConsultationAgendaItem::class, ['id' => 'agendaItemId']);
    }

    public function getMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    public function getSubqueues(): ActiveQuery
    {
        return $this->hasMany(SpeechSubqueue::class, ['queueId' => 'id'])->orderBy('position ASC');
    }

    public function getItems(): ActiveQuery
    {
        return $this->hasMany(SpeechQueueItem::class, ['queueId' => 'id']);
    }

    public function rules(): array
    {
        return [
            [['consultationId', 'isActive'], 'required'],
            [['isActive'], 'safe'],
            [['id', 'consultationId', 'agendaItemId', 'isActive'], 'number'],
        ];
    }

    private ?SpeechQueueSettings $settingsObject = null;

    public function getSettings(): SpeechQueueSettings
    {
        if (!is_object($this->settingsObject)) {
            $this->settingsObject = new SpeechQueueSettings($this->settings);
        }

        return $this->settingsObject;
    }

    public function setSettings(?SpeechQueueSettings $settings): void
    {
        $this->settingsObject = $settings;
        $this->settings = json_encode($settings, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }

    public function getAdminLink(): string
    {
        if ($this->motionId) {
            return UrlHelper::createMotionUrl($this->motion, 'admin-speech');
        } elseif ($this->agendaItemId) {
            return UrlHelper::createUrl(['/consultation/admin-speech', 'queue' => $this->id]);
        } else {
            return UrlHelper::createUrl(['/consultation/admin-speech']);
        }
    }

    public function getTitle(): string
    {
        $consultation = $this->getMyConsultation();
        if ($this->motionId && $consultation->getMotion($this->motionId)) {
            $motion = $consultation->getMotion($this->motionId);

            return str_replace('%TITLE%', $motion->getFormattedTitlePrefix(), \Yii::t('speech', 'title_to'));
        } elseif ($this->agendaItemId && $consultation->getAgendaItem($this->agendaItemId)) {
            $item = $consultation->getAgendaItem($this->agendaItemId);
            $title = $item->getShownCode(true) . ' ' . $item->title;

            return str_replace('%TITLE%', $title, \Yii::t('speech', 'title_to'));
        } else {
            return \Yii::t('speech', 'title_plain');
        }
    }

    public function getTitleShort(): string
    {
        $consultation = $this->getMyConsultation();
        if ($this->motionId && $consultation->getMotion($this->motionId)) {
            $motion = $consultation->getMotion($this->motionId);

            return str_replace('%TITLE%', $motion->getFormattedTitlePrefix(), \Yii::t('speech', 'footer_title_to'));
        } elseif ($this->agendaItemId && $consultation->getAgendaItem($this->agendaItemId)) {
            $item = $consultation->getAgendaItem($this->agendaItemId);

            return str_replace('%TITLE%', $item->getShownCode(true), \Yii::t('speech', 'footer_title_to'));
        } else {
            return \Yii::t('speech', 'footer_title_plain');
        }
    }

    /**
     * @param string[] $names
     */
    public function setSubqueueConfiguration(array $names): void
    {
        if (count($names) > 1) {
            for ($i = 0; $i < count($this->subqueues); $i++) {
                $subqueue = $this->subqueues[$i];
                if ($i < count($names)) {
                    $subqueue->name = $names[$i];
                    $subqueue->save();
                } else {
                    $subqueue->deleteReassignItems($this);
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

    public static function createWithSubqueues(Consultation $consultation, bool $activate): SpeechQueue
    {
        $queue                 = new SpeechQueue();
        $queue->consultationId = $consultation->id;
        $queue->motionId       = null;
        $queue->agendaItemId   = null;
        $queue->isActive       = ($activate ? 1 : 0);
        $queue->settings       = null;
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

    public function deleteWithSubqueues(): void
    {
        if ($this->agendaItemId === null && $this->motionId === null) {
            // The default queue cannot be deleted
            return;
        }

        foreach ($this->items as $item) {
            $item->delete();
        }
        foreach ($this->subqueues as $subqueue) {
            $subqueue->delete();
        }
        $this->delete();
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

    public function createItemOnAppliedList(string $name, ?SpeechSubqueue $subqueue, ?User $user, ?CookieUser $cookieUser, bool $pointOfOrder): SpeechQueueItem
    {
        $position = -1;
        foreach ($this->items as $item) {
            if ($item->position <= $position) {
                $position = $item->position - 1;
            }
        }

        if ($pointOfOrder) {
            $name = SpeechQueueItem::POO_MARKER . ' ' . $name;
        }

        $item              = new SpeechQueueItem();
        $item->queueId     = $this->id;
        $item->subqueueId  = $subqueue?->id;
        $item->userId      = $user?->id;
        $item->userToken   = $cookieUser?->userToken;
        $item->name        = $name;
        $item->position    = $position;
        $item->dateApplied = date('Y-m-d H:i:s');
        $item->dateStarted = null;
        $item->dateStopped = null;
        $item->save();

        $this->refresh();

        return $item;
    }

    public function startItem(SpeechQueueItem $item): void
    {
        $item->dateStarted = date("Y-m-d H:i:s");
        $item->dateStopped = null;
        $item->save();

        foreach ($this->items as $cmpItem) {
            if ($cmpItem->id !== $item->id && $cmpItem->dateStarted !== null && $cmpItem->dateStopped === null) {
                $cmpItem->dateStopped = date("Y-m-d H:i:s");
                $cmpItem->save();
            }
        }
    }

    /**
     * @return SpeechQueueItem[]
     */
    public function getSortedItems(?SpeechSubqueue $subqueue): array
    {
        $itemsApplied = [];
        foreach ($this->items as $item) {
            if (!(($subqueue && $subqueue->id === $item->subqueueId) || ($subqueue === null && $item->subqueueId === null))) {
                continue;
            }
            $itemsApplied[] = $item;
        }
        usort($itemsApplied, function (SpeechQueueItem $item1, SpeechQueueItem $item2): int {
            if ($item1->isPointOfOrder() && !$item2->isPointOfOrder()) {
                return -1;
            }
            if (!$item1->isPointOfOrder() && $item2->isPointOfOrder()) {
                return 1;
            }
            // Numbers are reversed, hence e.g. -5 should come before -7
            return $item2->position <=> $item1->position;
        });
        return $itemsApplied;
    }

    /**
     * @return ConsultationAgendaItem[]
     */
    public static function findAvailableAgendaItems(Consultation $consultation): array
    {
        $usedAgendaItems = [];
        foreach ($consultation->speechQueues as $speechQueue) {
            if ($speechQueue->agendaItemId !== null) {
                $usedAgendaItems[] = $speechQueue->agendaItemId;
            }
        }

        $unusedItems = [];
        foreach (ConsultationAgendaItem::getSortedFromConsultation($consultation) as $agendaItem) {
            if (!in_array($agendaItem->id, $usedAgendaItems)) {
                $unusedItems[] = $agendaItem;
            }
        }

        return $unusedItems;
    }
}
