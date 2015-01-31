<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $motionId
 * @property string $titlePrefix
 * @property string $changedTitle
 * @property string $changedParagraphs
 * @property string $changedExplanation
 * @property string $changeMetatext
 * @property string $changeText
 * @property string $changeExplanation
 * @property int $changeExplanationHtml
 * @property int $cacheCirstLineChanged
 * @property int $cacheCirstLineRel
 * @property int $cacheCirstLineAbs
 * @property string $dateCreation
 * @property string $dateResolution
 * @property int $status
 * @property string $statusString
 * @property string $noteInternal
 * @property int $textFixed
 *
 * @property Motion $motion
 * @property AmendmentComment[] $comments
 * @property AmendmentSupporter[] $supporters
 */
class Amendment extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotion()
    {
        return $this->hasOne(Motion::className(), ['id' => 'motionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::className(), ['amendmentId' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupporters()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['amendmentId' => 'id']);
    }
}
