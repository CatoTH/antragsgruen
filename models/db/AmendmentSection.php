<?php

namespace app\models\db;

use app\components\diff\Diff;
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
    use CacheTrait;

    /** @var null|MotionSection */
    private $_originalMotionSection = null;

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
        return $this->hasOne(ConsultationSettingsMotionSection::class, ['id' => 'sectionId']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAmendment()
    {
        return $this->hasOne(Amendment::class, ['id' => 'amendmentId']);
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
        if ($this->_originalMotionSection === null) {
            foreach ($this->amendment->motion->sections as $section) {
                if ($section->sectionId == $this->sectionId) {
                    $this->_originalMotionSection = $section;
                }
            }
        }
        return $this->_originalMotionSection;
    }

    /**
     * @param MotionSection $motionSection
     */
    public function setOriginalMotionSection(MotionSection $motionSection)
    {
        $this->_originalMotionSection = $motionSection;
    }

    /**
     * @return int
     * @throws Internal
     */
    public function getFirstLineNumber()
    {
        $cached = $this->getCacheItem('getFirstLineNumber');
        if ($cached !== null) {
            return $cached;
        }

        $first = $this->amendment->motion->getFirstLineNumber();
        foreach ($this->amendment->getSortedSections() as $section) {
            /** @var AmendmentSection $section */
            if ($section->sectionId == $this->sectionId) {
                $this->setCacheItem('getFirstLineNumber', $first);
                return $first;
            }
            if (!$section || !$section->getOriginalMotionSection()) {
                throw new Internal('Did not find myself');
            }
            $first += $section->getOriginalMotionSection()->getNumberOfCountableLines();
        }
        throw new Internal('Did not find myself');
    }

    /**
     * @param array $origParagraphs
     * @return MotionSectionParagraphAmendment[]
     */
    public function diffToOrigParagraphs($origParagraphs)
    {
        $cached = $this->getCacheItem('diffToOrigParagraphs');
        if ($cached !== null) {
            return $cached;
        }

        $diff         = new Diff();
        $amParagraphs = $diff->computeAmendmentParagraphDiff($origParagraphs, $this);

        $this->setCacheItem('diffToOrigParagraphs', $amParagraphs);
        return $amParagraphs;
    }

    /**
     * @param array $origParagraphs
     * @return string[]
     */
    public function getAffectedParagraphs($origParagraphs)
    {
        $amParas      = HTMLTools::sectionSimpleHTML($this->data);
        $diff         = new Diff();
        $amParagraphs = $diff->computeAmendmentAffectedParagraphs($origParagraphs, $amParas);
        return $amParagraphs;
    }
}
