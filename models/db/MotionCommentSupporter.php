<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveQuery;
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
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionCommentSupporter';
    }

    public function getMotionComment(): ActiveQuery
    {
        return $this->hasOne(MotionComment::class, ['id' => 'motionCommentId']);
    }
}
