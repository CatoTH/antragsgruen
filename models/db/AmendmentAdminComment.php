<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;

/**
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
        return AntragsgruenApp::getInstance()->tablePrefix . 'amendmentAdminComment';
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
            ['text', 'required', 'message' => 'Please enter a message.'],
            [['id', 'amendmentId', 'status'], 'number'],
            [['text'], 'safe'],
        ];
    }
}
