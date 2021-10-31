<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $ipHash
 * @property string $cookieId
 * @property int $motionCommentId
 * @property int $likes
 *
 * @property MotionComment $motionComment
 */
class MotionCommentSupporter extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionCommentSupporter';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionComment()
    {
        return $this->hasOne(MotionComment::class, ['id' => 'motionCommentId']);
    }
}
