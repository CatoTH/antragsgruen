<?php

namespace app\models\db;

use app\models\settings\AntragsgruenApp;
use yii\db\{ActiveQuery, ActiveRecord};

/**
 * @property int $id
 * @property int $siteId
 * @property string $title
 * @property string $texLayout
 * @property string $texContent
 *
 * @property Site $site
 * @property ConsultationMotionType $motionTypes
 */
class TexTemplate extends ActiveRecord
{
    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'texTemplate';
    }

    /**
     * @return ActiveQuery<Site>
     */
    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    /**
     * @return ActiveQuery<ConsultationMotionType>
     */
    public function getMotionTypes(): ActiveQuery
    {
        return $this->hasMany(ConsultationMotionType::class, ['texTemplateId' => 'id']);
    }

    public function rules(): array
    {
        return [
            [['title', 'texLayout', 'texContent'], 'required'],
            [['title', 'texLayout', 'texContent'], 'safe'],
        ];
    }
}
