<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property string $text_id
 * @property int $consultation_id
 * @property string $tet
 * @property string $edit_date
 *
 * @property Consultation $consultation
 */
class ConsultationText extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultation_text';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultation_id']);
    }
}