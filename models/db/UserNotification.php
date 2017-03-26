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
    const NOTIFICATION_NEW_MOTION          = 0;
    const NOTIFICATION_NEW_AMENDMENT       = 1;
    const NOTIFICATION_NEW_COMMENT         = 2;
    const NOTIFICATION_AMENDMENT_MY_MOTION = 3;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'userNotification';
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

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['userId', 'consultationId', 'notificationType'], 'required'],
            [['id', 'userId', 'consultationId', 'notificationType', 'notificationReferenceId'], 'number'],
        ];
    }

    /**
     * @param Consultation $consultation
     * @param null|int $notiType
     * @return UserNotification[]
     */
    public static function getConsultationNotifications(Consultation $consultation, $notiType = null)
    {
        if ($notiType) {
            $notifications = [];
            foreach ($consultation->userNotifications as $userNotification) {
                if ($userNotification->notificationType == $notiType) {
                    $notifications[] = $userNotification;
                }
            }
            return $notifications;
        } else {
            return $consultation->userNotifications;
        }
    }

    /**
     * @param User $user
     * @param Consultation $consultation
     * @return static[]
     */
    public static function getUserConsultationNotis(User $user, Consultation $consultation)
    {
        return static::findAll([
            'userId'         => $user->id,
            'consultationId' => $consultation->id,
        ]);
    }

    /**
     * @param User $user
     * @param Consultation $consultation
     * @param int $type
     * @param int|null $refId
     * @return static|null
     */
    public static function getNotification(User $user, Consultation $consultation, $type, $refId = null)
    {
        return static::findOne([
            'userId'                  => $user->id,
            'consultationId'          => $consultation->id,
            'notificationType'        => $type,
            'notificationReferenceId' => $refId,
        ]);
    }

    /**
     * @param User $user
     * @param Consultation $consultation
     * @param int $type
     * @param int|null $refId
     */
    public static function addNotification(User $user, Consultation $consultation, $type, $refId = null)
    {
        $noti = static::getNotification($user, $consultation, $type, $refId);
        if (!$noti) {
            $noti                          = new static();
            $noti->consultationId          = $consultation->id;
            $noti->userId                  = $user->id;
            $noti->notificationType        = $type;
            $noti->notificationReferenceId = $refId;
            $noti->save();
        }
    }

    /**
     * @param User $user
     * @param Consultation $consultation
     * @param int $type
     * @param int|null $refId
     */
    public static function removeNotification(User $user, Consultation $consultation, $type, $refId = null)
    {
        $noti = static::getNotification($user, $consultation, $type, $refId);
        if ($noti) {
            $noti->delete();
        }
    }
}
