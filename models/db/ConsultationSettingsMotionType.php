<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property string $title
 * @property string $motionPrefix
 * @property int $hasAmendments
 * @property int $position
 * @property int $cssicon
 *
 * @property Consultation $consultation
 * @property Motion[] $motions
 */
class ConsultationSettingsMotionType extends ActiveRecord
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationSettingsMotionType';
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
        return $this->hasMany(Motion::className(), ['motionTypeId' => 'id']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'title'], 'required'],
            [['id', 'consultationId', 'position', 'hasAmendments'], 'number'],
            [['title', 'position', 'motionPrefix', 'hasAmendments'], 'safe'],
        ];
    }
}
