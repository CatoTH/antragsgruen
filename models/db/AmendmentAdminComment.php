<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveQuery;

/**
 * @property int $amendmentId
 * @property Amendment $amendment
 */
class AmendmentAdminComment extends IAdminComment
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'amendmentAdminComment';
    }

    public function getAmendment(): ActiveQuery
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
    }

    public function rules(): array
    {
        return [
            [['amendmentId', 'status', 'dateCreation'], 'required'],
            ['text', 'required', 'message' => 'Please enter a message.'],
            [['id', 'amendmentId', 'status'], 'number'],
            [['text'], 'safe'],
        ];
    }
}
