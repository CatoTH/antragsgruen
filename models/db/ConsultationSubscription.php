<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $consultationId
 * @property int $userId
 * @property int $motions
 * @property int $amendments
 * @property int $comments
 *
 * @property Consultation $consultation
 */
class ConsultationSubscription extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationSubscription';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'userId']);
    }
}
