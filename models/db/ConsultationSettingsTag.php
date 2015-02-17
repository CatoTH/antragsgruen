<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $position
 * @property string $title
 * @property int $cssicon
 *
 * @property Consultation $consultation
 * @property Motion[] $motions
 */
class ConsultationSettingsTag extends ActiveRecord
{
    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationSettingsTag';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultation()
    {
        return $this->hasOne(Consultation::className(), ['id' => 'consultationId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotions()
    {
        return $this->hasMany(Motion::className(), ['id' => 'motionId'])->viaTable('motionTag', ['tagId' => 'id']);
    }

    /**
     * @return string
     */
    public function getCSSIconClass()
    {
        switch ($this->cssicon) {
            default:
                return 'glyphicon glyphicon-file';
        }
    }
}
