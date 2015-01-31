<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $motion_id
 * @property string $title_prefix
 * @property string $changed_title
 * @property string $changed_paragraphs
 * @property string $changed_explanation
 * @property string $change_metatext
 * @property string $change_text
 * @property string $change_explanation
 * @property int $change_explanation_html
 * @property int $cache_first_line_changed
 * @property int $cache_first_line_rel
 * @property int $cache_first_line_avs
 * @property string $date_creation
 * @property string $date_resolution
 * @property int $status
 * @property string $status_string
 * @property string $note_internal
 * @property int $text_fixed
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
        return $this->hasOne(Motion::className(), ['id' => 'motion_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComments()
    {
        return $this->hasMany(AmendmentComment::className(), ['amendment_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSupporters()
    {
        return $this->hasMany(AmendmentSupporter::className(), ['amendment_id' => 'id']);
    }

}