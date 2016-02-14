<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property string $emailHash
 */
class EMailBlacklist extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'emailBlacklist';
    }

    /**
     * @param string $email
     * @return bool
     */
    public static function isBlacklisted($email)
    {
        $blacklist = static::findOne(md5(mb_strtolower(trim($email))));
        return ($blacklist !== null);
    }

    /**
     * @param string $email
     */
    public static function addToBlacklist($email)
    {
        $blacklist            = new EMailBlacklist();
        $blacklist->emailHash = md5(mb_strtolower(trim($email)));
        $blacklist->save();
    }

    /**
     * @param string $email
     */
    public static function removeFromBlacklist($email)
    {
        /** @var EMailBlacklist $blacklist */
        $blacklist = static::findOne(md5(mb_strtolower(trim($email))));
        if ($blacklist) {
            $blacklist->delete();
        }
    }
}
