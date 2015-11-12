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
 * @property AmendmentSection
 */
class AmendmentSection extends IMotionSection
{
    use CacheTrait;

    /** @var null|MotionSection */
    private $originalMotionSection = null;

    /**
     * @return string
     */
    public static function tableName()
    {
        return 'amendmentSection';
    }

    /**
     * @return ConsultationSettingsMotionSection
     */
    public function getSettings()
    {
        $section = $this->getOriginalMotionSection();
        if ($section) {
            return $section->getSettings();
        } else {
            /** @var ConsultationSettingsMotionSection $section */
            return ConsultationSettingsMotionSection::findOne($this->sectionId);
        }
    }

    /**
     * @return Amendment|null
     */
    public function getAmendment()
    {
        return $this->getCachedConsultation()->getAmendment($this->amendmentId);
    }

    /**
     * @return Motion|null
     */
    public function getMotion()
    {
        if ($this->originalMotionSection) {
            return $this->originalMotionSection->getMotion();
        }
        if ($this->amendmentId === null) {
            return null;
        }
        return $this->getCachedConsultation()->getMotion($this->getAmendment()->motionId);
    }

    /**
     * @return Consultation|null
     */
    public function getCachedConsultation()
    {
        $current = Consultation::getCurrent();
        if ($current) {
            $amend = $current->getAmendment($this->amendmentId);
            if ($amend) {
                return $current;
            }
        }
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne($this->amendmentId);
        if ($amendment) {
            return $amendment->getMyConsultation();
        } else {
            return null;
        }
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
        if ($this->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Paragraphs are only available for simple text sections.');
        }
        return HTMLTools::sectionSimpleHTML($this->data);
    }

    /**
     * @return MotionSection|null
     */
    public function getOriginalMotionSection()
    {
        if ($this->originalMotionSection === null) {
            $motion = $this->getMotion();
            if ($motion) {
                foreach ($this->getMotion()->sections as $section) {
                    if ($section->sectionId == $this->sectionId) {
                        $this->originalMotionSection = $section;
                    }
                }
            } else {
                null;
            }
        }
        return $this->originalMotionSection;
    }

    /**
     * @param MotionSection $motionSection
     */
    public function setOriginalMotionSection(MotionSection $motionSection)
    {
        $this->originalMotionSection = $motionSection;
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

        $first = $this->getMotion()->getFirstLineNumber();
        foreach ($this->getAmendment()->getSortedSections() as $section) {
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
