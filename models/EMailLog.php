<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property string $to_email
 * @property int $to_user_id
 * @property int $type
 * @property string $from_email
 * @property string $date_sent
 * @property string $subjet
 * @property string $text
 *
 * @property User $to_user
 */
class EMailLog extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'email_log';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'to_user_id']);
    }
}