<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $queueId
 * @property string $name
 * @property int $position
 *
 * @property SpeechQueue $queue
 */
class SpeechSubqueue extends ActiveRecord
{
    public const CONFIGURATION_NONE = 0;
    public const CONFIGURATION_GENDER = 1;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'speechSubqueue';
    }

    public function getQueue(): ActiveQuery
    {
        return $this->hasOne(SpeechQueue::class, ['id' => 'queueId']);
    }

    public function rules(): array
    {
        return [
            [['queueId', 'name', 'position'], 'required'],
            [['name', 'position'], 'safe'],
            [['id', 'queueId', 'position'], 'number'],
        ];
    }

    /**
     * @return SpeechQueueItem[]
     */
    public function getItems(SpeechQueue $queue): array
    {
        $items = [];
        foreach ($queue->items as $item) {
            if ($item->subqueueId === $this->id) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function deleteReassignItems(SpeechQueue $queue): void
    {
        foreach ($this->getItems($queue) as $item) {
            $item->subqueueId = null;
            $item->save();
        }

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->delete();
    }
}
