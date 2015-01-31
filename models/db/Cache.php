<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property string $dateCreated
 * @property string $data
 */
class Cache extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'cache';
    }
}
