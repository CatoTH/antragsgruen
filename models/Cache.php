<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * @package app\models
 *
 * @property int $id
 * @property string $date_created
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