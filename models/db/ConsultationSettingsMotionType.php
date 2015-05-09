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
 * @property ConsultationSettingsMotionSection[] $motionSections
 * @property Motion[] $motions
 * @property ConsultationAgendaItem[] $agendaItems
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
     * @return \yii\db\ActiveQuery
     */
    public function getMotionSections()
    {
        return $this->hasMany(ConsultationSettingsMotionSection::className(), ['motionTypeId' => 'id'])
            ->where('status = ' . ConsultationSettingsMotionSection::STATUS_VISIBLE)
            ->orderBy('position');
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAgendaItems()
    {
        return $this->hasMany(ConsultationAgendaItem::className(), ['motionTypeId' => 'id']);
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
