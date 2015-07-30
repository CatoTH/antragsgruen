<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $userId
 * @property int $consultationId
 * @property int $notificationType
 * @property int $notificationReferenceId
 * @property string $lastNotification
 *
 * @property Consultation $consultation
 * @property User $user
 */
class UserNotification extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'userNotification';
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
        return $this->hasOne(User::className(), ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }
}
