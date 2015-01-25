<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $consultation_id
 * @property int $user_id
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
        return 'consultation_subscription';
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
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }
}