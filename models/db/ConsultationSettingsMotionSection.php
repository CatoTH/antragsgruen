<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $type
 * @property int $position
 * @property string $title
 * @property int $maxLen
 * @property int $fixedWidth
 * @property int $lineNumbers
 *
 * @property Consultation $consultation
 * @property MotionSection[] $sections
 */
class ConsultationSettingsMotionSection extends ActiveRecord
{
    const TYPE_TITLE = 0;
    const TYPE_TEXT_PLAIN = 1;
    const TYPE_TEXT_HTML = 2;
    const TYPE_IMAGE = 3;

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'consultationSettingsMotionSection';
    }

    /**
     * @return string[]
     */
    public static function getTypes()
    {
        return array(
            0 => "Titel",
            1 => "Text",
            2 => "Text (erweitert)",
            3 => "Bild",
        );
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
    public function getSections()
    {
        return $this->hasMany(MotionSection::className(), ['sectionId' => 'id']);
    }
}
