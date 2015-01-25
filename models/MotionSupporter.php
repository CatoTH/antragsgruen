<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $motion_id
 * @property int $position
 * @property int $user_id
 * @property string $role
 * @property string $comment
 * @property string $person_type
 * @property string $name
 * @property string $organization
 * @property string $resolution_date
 * @property string $contact_email
 * @property string $context_phone
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
        return 'amendment_supporter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motion_id']);
    }
}
