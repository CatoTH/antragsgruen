<?php

declare(strict_types=1);

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\ActiveQuery;

/**
 * @property int $motionId
 * @property Motion $motion
 */
class MotionAdminComment extends IAdminComment
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'motionAdminComment';
    }

    public function getMotion(): ActiveQuery
    {
        return $this->hasOne(Motion::class, ['id' => 'motionId']);
    }

    public function rules(): array
    {
        return [
            [['motionId', 'status', 'dateCreation'], 'required'],
            ['text', 'required', 'message' => 'Please enter a message.'],
            [['id', 'motionId', 'status'], 'number'],
            [['text'], 'safe'],
        ];
    }
}
