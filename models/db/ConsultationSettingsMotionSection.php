<?php
namespace app\models\db;

use yii\db\ActiveRecord;

/**
 * @package app\models\db
 *
 * @property int $id
 * @property int $consultationId
 * @property int $motionTypeId
 * @property int $type
 * @property int $position
 * @property string $title
 * @property int $maxLen
 * @property int $fixedWidth
 * @property int $lineNumbers
 * @property int $hasComments
 *
 * @property Consultation $consultation
 * @property MotionSection[] $sections
 * @property ConsultationSettingsMotionType $motionType
 */
class ConsultationSettingsMotionSection extends ActiveRecord
{
    const TYPE_TITLE       = 0;
    const TYPE_TEXT_SIMPLE = 1;
    const TYPE_TEXT_HTML   = 2;
    const TYPE_IMAGE       = 3;

    const COMMENTS_NONE       = 0;
    const COMMENTS_SECTION    = 1;
    const COMMENTS_PARAGRAPHS = 2;

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
        return [
            static::TYPE_TITLE       => 'Titel',
            static::TYPE_TEXT_SIMPLE => 'Text',
            static::TYPE_TEXT_HTML   => 'Text (erweitert)',
            static::TYPE_IMAGE       => 'Bild',
        ];
    }

    /**
     * @return string[]
     */
    public static function getCommentTypes()
    {
        return [
            static::COMMENTS_NONE       => 'Keine Kommentare',
            static::COMMENTS_SECTION    => 'Abschnitt als ganzes kommentierbar',
            static::COMMENTS_PARAGRAPHS => 'Einzelne Absätze sind kommentierbar'
        ];
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

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMotionType()
    {
        return $this->hasOne(ConsultationSettingsMotionType::className(), ['id' => 'motionTypeId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['consultationId', 'title', 'type', 'position'], 'required'],
            [['id', 'consultationId', 'type', 'motionTypeId'], 'number'],
            [['position', 'fixedWidth', 'maxLen', 'lineNumbers', 'hasComments'], 'number'],
            [['type', 'title', 'maxLen', 'hasComments'], 'safe'],
        ];
    }

    /**
     * @return int[]
     */
    public function getAvailableCommentTypes()
    {
        if ($this->type == static::TYPE_TEXT_SIMPLE) {
            return [static::COMMENTS_NONE, static::COMMENTS_SECTION, static::COMMENTS_PARAGRAPHS];
        }
        if ($this->type == static::TYPE_TEXT_HTML) {
            return [static::COMMENTS_NONE, static::COMMENTS_SECTION];
        }
        return [static::COMMENTS_NONE];
    }
}
