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
     * @return \yii\db\ActiveQuery
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
}
