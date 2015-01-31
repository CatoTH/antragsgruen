<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property int $position
 * @property int $userId
 * @property string $role
 * @property string $comment
 * @property string $personType
 * @property string $name
 * @property string $organization
 * @property string $resolutionDate
 * @property string $contactEmail
 * @property string $contextPhone
 *
 * @property User $user
 * @property Motion $motion
 */
class MotionSupporter extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendmentSupporter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motionId']);
    }
}
