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

    /**
     * @return string[]
     */
    public static function getTypes()
    {
        return array(
            0 => "Sonstiges",
            1 => "Registrierung",
            2 => "Benachrichtigung User",
            3 => "Benachrichtigung Admin",
            4 => "Namespaced_Angelegt",
            5 => "Debug",
        );
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
