<?php

namespace app\models\db;

use app\components\HTMLTools;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

/**
 * @package app\models\db
 *
 * @property int $amendmentId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property string $cache
 * @property string $metadata
 *
 * @property Amendment $amendment
 * @property ConsultationSettingsMotionSection $consultationSetting
 * @property AmendmentSection
 */
class AmendmentSection extends IMotionSection
{

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendmentSection';
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getConsultationSetting()
    {
        return $this->hasOne(ConsultationSettingsMotionSection::className(), ['id' => 'sectionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::className(), ['id' => 'amendmentId']);
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['amendmentId', 'sectionId'], 'required'],
            [['amendmentId', 'sectionId'], 'number'],
            [['dataRaw'], 'safe'],
        ];
    }

    /**
     * @return \string[]
     * @throws Internal
     */
    public function getTextParagraphs()
    {
        if ($this->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Paragraphs are only available for simple text sections.');
        }
        return HTMLTools::sectionSimpleHTML($this->data);
    }

    /**
     * @return MotionSection|null
     */
    public function getOriginalMotionSection()
    {
        foreach ($this->amendment->motion->sections as $section) {
            if ($section->sectionId == $this->sectionId) {
                return $section;
            }
        }
        return null;
    }

    /**
     * @return int
     * @throws Internal
     */
    public function getFirstLineNumber()
    {
        $first = $this->amendment->motion->getFirstLineNumber();
        foreach ($this->amendment->getSortedSections() as $section) {
            /** @var AmendmentSection $section */
            if ($section->sectionId == $this->sectionId) {
                return $first;
            }
            $first += $section->getOriginalMotionSection()->getNumberOfCountableLines();
        }
        throw new Internal('Did not find myself');
    }
}
