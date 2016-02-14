<?php

namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
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
    /**
     * @return string
     */
    public static function tableName()
    {
        /** @var \app\models\settings\AntragsgruenApp $app */
        $app = \Yii::$app->params;
        return $app->tablePrefix . 'texTemplate';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSite()
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionTypes()
    {
        return $this->hasMany(ConsultationMotionType::class, ['texTemplateId' => 'id']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'texLayout', 'texContent'], 'required'],
            [['title', 'texLayout', 'texContent'], 'safe'],
        ];
    }
}
