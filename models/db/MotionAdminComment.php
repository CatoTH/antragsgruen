<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;

/**
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
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionAdminComment';
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
