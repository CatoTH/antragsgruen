<?php

declare(strict_types=1);

namespace app\models;

use app\components\diff\{AmendmentSectionFormatter, DataTypes\AffectedLineBlock, DiffRenderer};
use app\models\db\{IMotionSection, Motion, MotionSection};
use app\models\exceptions\{Inconsistency, Internal};
use app\models\sectionTypes\ISectionType;

class MotionSectionChanges
{
    public ?MotionSection $oldSection;
    public ?MotionSection $newSection;

    public function __construct(?MotionSection $oldSection, ?MotionSection $newSection)
    {
        $this->newSection = $newSection;
        $this->oldSection = $oldSection;
    }

    /**
     * @return MotionSectionChanges[]
     * @throws Inconsistency
     */
    public static function motionToSectionChanges(Motion $oldMotion, Motion $newMotion): array
    {
        if (!$oldMotion->getMyMotionType()->isCompatibleTo($newMotion->getMyMotionType(), [])) {
            throw new Inconsistency('The two motions have incompatible types');
        }

        /** @var MotionSection[] $sectionsOld */
        $sectionsOld = $oldMotion->getSortedSections(false);
        /** @var MotionSection[] $sectionsNew */
        $sectionsNew = $newMotion->getSortedSections(false);
        $changes = [];
        for ($i = 0; $i < count($sectionsOld); $i++) {
            if (!isset($sectionsNew[$i])) {
                continue;
            }
            if (!$sectionsOld[$i]->getSettings()->hasAmendments && $sectionsNew[$i]->getSectionType()->isEmpty()) {
                // In resolutions, the reasons are usually deleted.
                // However, they should not be displayed as being deleted.
                continue;
            }
            if ($sectionsOld[$i]->getSettings()->type !== $sectionsNew[$i]->getSettings()->type) {
                throw new Inconsistency('The two motions have incompatible types');
            }
            $changes[]  = new MotionSectionChanges($sectionsOld[$i], $sectionsNew[$i]);
        }

        return $changes;
    }

    public function hasChanges(): bool
    {
        if (!$this->oldSection || !$this->newSection) {
            return false;
        }
        return ($this->oldSection->getData() !== $this->newSection->getData());
    }

    private function getAnySection(): MotionSection
    {
        if ($this->newSection) {
            return $this->newSection;
        } else {
            return $this->oldSection;
        }
    }

    public function getSectionId(): int
    {
        return $this->getAnySection()->sectionId;
    }

    public function getSectionTitle(): string
    {
        return $this->getAnySection()->getSettings()->title;
    }

    public function getSectionType(): MotionSection
    {
        return $this->getAnySection();
    }

    public function getSectionTypeId(): int
    {
        return $this->getAnySection()->getSettings()->type;
    }

    public function getFirstLineNumber(): int
    {
        return $this->getAnySection()->getFirstLineNumber();
    }

    /**
     * @return int
     */
    public function isFixedWithFont(): int
    {
        return $this->getAnySection()->getSettings()->fixedWidth;
    }

    /**
     * @return AffectedLineBlock[]
     */
    public function getSimpleTextDiffGroups(int $diffFormatting = DiffRenderer::FORMATTING_CLASSES): array
    {
        if (!$this->oldSection || !$this->newSection || $this->getSectionTypeId() !== ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Impossible to calculate diff');
        }

        $lineLength = $this->oldSection->getConsultation()->getSettings()->lineLength;
        $firstLine  = $this->oldSection->getFirstLineNumber();

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($this->oldSection->getData());
        $formatter->setTextNew($this->newSection->getData());
        $formatter->setFirstLineNo($firstLine);

        return $formatter->getDiffGroupsWithNumbers($lineLength, $diffFormatting);
    }
}
