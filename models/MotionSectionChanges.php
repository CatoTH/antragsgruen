<?php

namespace app\models;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DiffRenderer;
use app\models\db\Motion;
use app\models\db\MotionSection;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

/**
 * Class MotionSectionChanges
 * @package models
 */
class MotionSectionChanges
{
    public $oldSection;
    public $newSection;

    /**
     * MotionSectionChanges constructor.
     * @param MotionSection|null $oldSection
     * @param MotionSection|null $newSection
     */
    public function __construct($oldSection, $newSection)
    {
        $this->newSection = $newSection;
        $this->oldSection = $oldSection;
    }

    /**
     * @param Motion $oldMotion
     * @param Motion $newMotion
     * @return MotionSectionChanges[]
     */
    public static function motionToSectionChanges(Motion $oldMotion, Motion $newMotion)
    {
        $sectionsOld = [];
        foreach ($oldMotion->getSortedSections(false) as $section) {
            $sectionsOld[$section->sectionId] = $section;
        }

        $changes = [];
        /** @var MotionSection[] $sections */
        $sections = $newMotion->getSortedSections(false);
        foreach ($sections as $section) {
            $oldSection = (isset($sectionsOld[$section->sectionId]) ? $sectionsOld[$section->sectionId] : null);
            $changes[]  = new MotionSectionChanges($oldSection, $section);
        }

        return $changes;
    }

    /**
     * @return bool
     */
    public function hasChanges()
    {
        if (!$this->oldSection || !$this->newSection) {
            return false;
        }
        return ($this->oldSection->data != $this->newSection->data);
    }

    /**
     * @return MotionSection
     */
    private function getAnySection()
    {
        if ($this->newSection) {
            return $this->newSection;
        } else {
            return $this->oldSection;
        }
    }

    /**
     * @return int
     */
    public function getSectionId()
    {
        return $this->getAnySection()->sectionId;
    }

    /**
     * @return string
     */
    public function getSectionTitle()
    {
        return $this->getAnySection()->getSettings()->title;
    }

    /**
     * @return int
     */
    public function getSectionTypeId()
    {
        return $this->getAnySection()->getSettings()->motionTypeId;
    }

    /**
     * @return int
     * @throws Internal
     */
    public function getFirstLineNumber()
    {
        return $this->getAnySection()->getFirstLineNumber();
    }

    /**
     * @return int
     */
    public function isFixedWithFont()
    {
        return $this->getAnySection()->getSettings()->fixedWidth;
    }

    /**
     * @param int $diffFormatting
     * @return array
     * @throws Internal
     */
    public function getSimpleTextDiffGroups($diffFormatting = DiffRenderer::FORMATTING_CLASSES)
    {
        if (!$this->oldSection || !$this->newSection || $this->getSectionTypeId() !== ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Impossible to calculate diff');
        }

        $lineLength = $this->oldSection->getConsultation()->getSettings()->lineLength;
        $firstLine  = $this->oldSection->getFirstLineNumber();

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($this->oldSection->data);
        $formatter->setTextNew($this->newSection->data);
        $formatter->setFirstLineNo($firstLine);
        $diffGroups = $formatter->getDiffGroupsWithNumbers($lineLength, $diffFormatting);

        return $diffGroups;
    }
}
