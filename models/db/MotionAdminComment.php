<?php

namespace app\models\db;

/**
 * @package app\models\db
 *
 * @property int $motionId
 * @property Motion $motion
 */
class MotionAdminComment extends IAdminComment
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'motionAdminComment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['motionId', 'status', 'dateCreation'], 'required'],
            ['text', 'required', 'message' => 'Please enter a message.'],
            [['id', 'motionId', 'status'], 'number'],
            [['text'], 'safe'],
        ];
    }
}
