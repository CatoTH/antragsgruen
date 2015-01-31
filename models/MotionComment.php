<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $user_id
 * @property int $motion_id
 * @property string $text
 * @property string $date_created
 * @property int $status
 * @property int $reply_notification
 *
 * @property User $user
 * @property Motion $motion
 * @property MotionCommentSupporter[] $supporters
 */
class MotionComment extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motion_comment';
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupporters()
    {
        return $this->hasOne(MotionCommentSupporter::className(), ['motion_comment_id' => 'id']);
    }
}