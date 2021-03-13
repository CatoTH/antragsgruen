<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
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
    const CONFIGURATION_NONE = 0;
    const CONFIGURATION_GENDER = 1;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;

        return $app->tablePrefix . 'speechSubqueue';
    }

    /**
     * @return \Yii\db\ActiveQuery
     */
    public function getQueue()
    {
        return $this->hasOne(SpeechQueue::class, ['id' => 'queueId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['queueId', 'name', 'position'], 'required'],
            [['name', 'position'], 'safe'],
            [['id', 'queueId', 'position'], 'number'],
        ];
    }

    /**
     * @param SpeechQueue $queue
     *
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
