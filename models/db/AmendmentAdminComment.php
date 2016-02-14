<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $userId
 * @property int $amendmentId
 * @property string $dateCreation
 * @property string $text
 *
 * @property User $user
 * @property Amendment $amendment
 */
class AmendmentAdminComment extends ActiveRecord
{
    const STATUS_VISIBLE   = 0;
    const STATUS_DELETED   = -1;
    const STATUS_SCREENING = 1;

    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'amendmentAdminComment';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'userId'])
            ->andWhere(User::tableName() . '.status != ' . User::STATUS_DELETED);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['amendmentId', 'status', 'dateCreation'], 'required'],
            ['text', 'required', 'message' => 'Bitte gib etwas Text ein.'],
            [['id', 'amendmentId', 'status'], 'number'],
            [['text'], 'safe'],
        ];
    }
}
