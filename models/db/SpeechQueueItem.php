<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $queueId
 * @property int|null $subqueueId - null if there are no subqueues and the default queue is used
 * @property int|null $userId
 * @property string $name
 * @property int|null $position - null if the user has only applied; >0 once assigned to a speaking slot
 * @property string|null $dateApplied
 * @property string|null $dateStarted - the exact time when the speaking has started
 * @property string|null $dateStopped - the exact time when the speaking has stopped; relevant when queue-based timing information is calculated
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

    public function getDateApplied(): ?\DateTime
    {
        if ($this->dateApplied) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateApplied);
        } else {
            return null;
        }
    }

    public function getDateStarted(): ?\DateTime
    {
        if ($this->dateStarted) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateStarted);
        } else {
            return null;
        }
    }

    public function getDateStopped(): ?\DateTime
    {
        if ($this->dateStopped) {
            return \DateTime::createFromFormat('Y-m-d H:i:s', $this->dateStopped);
        } else {
            return null;
        }
    }
}
