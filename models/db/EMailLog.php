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
    const TYP_SONSTIGES                     = 0;
    const TYP_REGISTRIERUNG                 = 1;
    const TYP_ANTRAG_BENACHRICHTIGUNG_USER  = 2;
    const TYP_ANTRAG_BENACHRICHTIGUNG_ADMIN = 3;
    const TYP_NAMESPACED_ACCOUNT_ANGELEGT   = 4;
    const TYP_DEBUG                         = 5;

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
