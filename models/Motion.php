<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $consultation_id
 * @property int $parent_motion_id
 * @property string $title
 * @property string $title_prefix
 * @property string $date_creation
 * @property string $date_resolution
 * @property string $text
 * @property string $explanation
 * @property int $explanation_html
 * @property int $statuc
 * @property string $status_string
 * @property string $note_internal
 * @property int $cache_line_number
 * @property int $cache_paragraph_number
 * @property int $text_fixed
 *
 * @property Consultation $consultation
 * @property Amendment[] $amendments
 * @property MotionComment[] $comments
 * @property ConsultationTag[] $tags
 */
class Motion extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'motion';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(MotionComment::className(), ['motion_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupporters()
    {
        return $this->hasMany(MotionSupporter::className(), ['motion_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation() {
        return $this->hasOne(Consultation::className(), ['id' => 'consultation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendments()
    {
        return $this->hasOne(Amendment::className(), ['motion_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(ConsultationTag::className(), ['id' => 'tag_id'])->viaTable('motion_tag', ['motion_id' => 'id']);
    }


}
