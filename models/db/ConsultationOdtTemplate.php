<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
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
        return 'consultationOdtTemplate';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::class, ['id' => 'consultationId']);
    }
}
