<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property string $toEmail
 * @property int $toUserId
 * @property int $type
 * @property string $fromEmail
 * @property string $dateSent
 * @property string $subject
 * @property string $text
 *
 * @property User $toUser
 */
class EMailLog extends ActiveRecord
{
    const TYPE_OTHER                      = 0;
    const TYPE_REGISTRATION               = 1;
    const TYPE_MOTION_NOTIFICATION_USER   = 2;
    const TYPE_MOTION_NOTIFICATION_ADMIN  = 3;
    const TYPE_NAMESPACED_ACCOUNT_CREATED = 4;
    const TYPE_DEBUG                      = 5;
    const TYPE_PASSWORD_RECOVERY          = 6;

    /**
     * @return string[]
     */
    public static function getTypes()
    {
        return [
            static::TYPE_OTHER                      => "Sonstiges",
            static::TYPE_REGISTRATION               => "Registrierung",
            static::TYPE_MOTION_NOTIFICATION_USER   => "Benachrichtigung User",
            static::TYPE_MOTION_NOTIFICATION_ADMIN  => "Benachrichtigung Admin",
            static::TYPE_NAMESPACED_ACCOUNT_CREATED => "Namespaced_Angelegt",
            static::TYPE_DEBUG                      => "Debug",
            static::TYPE_PASSWORD_RECOVERY          => 'Password-Wiederherstellung',
        ];
    }


    /**
     * @return string
     */
    public static function tableName()
    {
        return 'emailLog';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'toUserId']);
    }
}
