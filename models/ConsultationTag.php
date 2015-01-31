<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $consultation_id
 * @property int $position
 * @property string $title
 *
 * @property Consultation $consultation
 * @property Motion[] $motions
 */
class ConsultationTag extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultation_tag';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultation_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::className(), ['id' => 'motion_id'])->viaTable('motion_tag', ['tag_id' => 'id']);
    }
}