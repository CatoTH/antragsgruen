<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property int $consultation_id
 * @property int $type
 * @property string $data
 *
 * @property Consultation $consultation
 */
class ConsultationOdtTemplate extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultation_odt_template';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultation_id']);
    }
}