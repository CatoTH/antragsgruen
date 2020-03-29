<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $queueId
 * @property int|null $subqueueId
 * @property int|null $userId
 * @property string $name
 * @property int $position
 * @property string|null $dateStarted
 * @property string|null $dateStopped
 *
 * @property SpeechQueue $speechQueue
 * @property User|null $user
 */
class SpeechQueueItem extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var AntragsgruenApp $app */
        $app = \Yii::$app->params;

        return $app->tablePrefix . 'speechQueueItem';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSpeechQueue()
    {
        return $this->hasOne(SpeechQueue::class, ['id' => 'agendaItemId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['queueId', 'name', 'position'], 'required'],
            [['id', 'queueId', 'subqueueId', 'userId', 'position'], 'number'],
        ];
    }
}
