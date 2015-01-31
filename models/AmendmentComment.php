<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $user_id
 * @property int $amendment_id
 * @property string $text
 * @property string $date_created
 * @property int $status
 * @property int $reply_notification
 *
 * @property User $user
 * @property Amendment $amendment
 */
class AmendmentComment extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendment_comment';
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
    public function getAmendment()
    {
        return $this->hasOne(Amendment::className(), ['id' => 'amendment_id']);
    }
}