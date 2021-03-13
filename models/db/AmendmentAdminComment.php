<?php

namespace app\models\db;

/**
 * @package app\models\db
 *
 * @property int $amendmentId
 * @property Amendment $amendment
 */
class AmendmentAdminComment extends IAdminComment
{
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
     * @return \Yii\db\ActiveQuery
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
            ['text', 'required', 'message' => 'Please enter a message.'],
            [['id', 'amendmentId', 'status'], 'number'],
            [['text'], 'safe'],
        ];
    }
}
