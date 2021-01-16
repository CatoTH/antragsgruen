<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @property string $emailHash
 */
class EMailBlocklist extends ActiveRecord
{
    public static function tableName(): string
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'emailBlocklist';
    }

    public static function isBlocked(string $email): bool
    {
        $blocklist = static::findOne(md5(mb_strtolower(trim($email))));
        return ($blocklist !== null);
    }

    public static function addToBlocklist(string $email): void
    {
        if (static::isBlocked($email)) {
            // Prevent duplicate entries / sql integrity errors
            return;
        }
        $blocklist            = new EMailBlocklist();
        $blocklist->emailHash = md5(mb_strtolower(trim($email)));
        $blocklist->save();
    }

    public static function removeFromBlocklist(string $email): void
    {
        /** @var EMailBlocklist $blocklist */
        $blocklist = static::findOne(md5(mb_strtolower(trim($email))));
        if ($blocklist) {
            $blocklist->delete();
        }
    }
}
