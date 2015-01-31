<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property string $ip_hash
 * @property string $cookie_id
 * @property int $motion_comment_id
 * @property int $likes
 *
 * @property MotionComment $motion_comment
 */
class MotionCommentSupporter extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motion_comment_supoorter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion_comment()
    {
        return $this->hasOne(MotionComment::className(), ['id' => 'motion_comment_id']);
    }
}