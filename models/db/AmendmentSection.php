<?php

namespace app\models\db;

use app\models\SectionedParagraph;
use app\models\settings\AntragsgruenApp;
use app\components\{diff\AmendmentRewriter, diff\ArrayMatcher, diff\Diff, diff\DiffRenderer, HTMLTools, LineSplitter};
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

/**
 * @property int|null $amendmentId
 * @property int $sectionId
 * @property string $data
 * @property string $dataRaw
 * @property int $public
 * @property string $cache
 * @property string $metadata
 */
class AmendmentSection extends IMotionSection
{
    private ?MotionSection $originalMotionSection = null;

    public static function tableName(): string
    {
        return AntragsgruenApp::getInstance()->tablePrefix . 'amendmentSection';
    }

    public function getSettings(): ?ConsultationSettingsMotionSection
    {
        $section = $this->getOriginalMotionSection();
        if ($section) {
            return $section->getSettings();
        } else {
            return ConsultationSettingsMotionSection::findOne($this->sectionId);
        }
    }

    public function getAmendment(): ?Amendment
    {
        if ($this->amendmentId === null) {
            return null;
        }
        return $this->getCachedConsultation()->getAmendment($this->amendmentId);
    }

    public function getMotion(): ?Motion
    {
        if ($this->originalMotionSection) {
            return $this->originalMotionSection->getMotion();
        }
        if ($this->amendmentId === null) {
            return null;
        }
        return $this->getCachedConsultation()->getMotion($this->getAmendment()->motionId);
    }

    public function getCachedConsultation(): ?Consultation
    {
        $current = Consultation::getCurrent();
        if ($current) {
            if ($this->amendmentId === null) {
                return $current;
            }
            $amend = $current->getAmendment($this->amendmentId);
            if ($amend) {
                return $current;
            }
        }
        $amendment = Amendment::findOne($this->amendmentId);

        return $amendment?->getMyConsultation();
    }

    public function rules(): array
    {
        return [
            [['amendmentId', 'sectionId'], 'required'],
            [['amendmentId', 'sectionId'], 'number'],
            [['dataRaw'], 'safe'],
        ];
    }

    public function getOriginalMotionSection(): ?MotionSection
    {
        if ($this->originalMotionSection === null) {
            $motion = $this->getMotion();
            if ($motion) {
                foreach ($this->getMotion()->getActiveSections() as $section) {
                    if ($section->sectionId === $this->sectionId) {
                        $this->originalMotionSection = $section;
                    }
                }
            } else {
                return null;
            }
        }
        return $this->originalMotionSection;
    }

    public function setOriginalMotionSection(MotionSection $motionSection): void
    {
        $this->originalMotionSection = $motionSection;
    }

    public function getFirstLineNumber(): int
    {
        $first = $this->getMotion()->getFirstLineNumber();
        foreach ($this->getAmendment()->getSortedSections() as $section) {
            /** @var AmendmentSection $section */
            if ($section->sectionId === $this->sectionId) {
                return $first;
            }
            if (!$section->getOriginalMotionSection()) {
                throw new Internal('Did not find myself');
            }
            $first += $section->getOriginalMotionSection()->getNumberOfCountableLines();
        }
        throw new Internal('Did not find myself');
    }

    /**
     * Paragraph numbering starts at 0
     * The last returned paragraph number does not actually exist but exists to calculate the length of the
     * actual last paragraph
     *
     * @return int[]
     */
    public function getParagraphLineNumberHelper(): array
    {
        $motionParas = HTMLTools::sectionSimpleHTML($this->getOriginalMotionSection()->getData());
        $motionParas = array_map(fn(SectionedParagraph $par) => $par->html, $motionParas);
        $lineLength      = $this->getMotion()->getMyConsultation()->getSettings()->lineLength;
        $lineNumber      = $this->getFirstLineNumber();
        $paraLineNumbers = [];
        for ($paraNo = 0; $paraNo < count($motionParas); $paraNo++) {
            $paraLineNumbers[$paraNo] = $lineNumber;
            $lineNumber += LineSplitter::countMotionParaLines($motionParas[$paraNo], $lineLength);
        }
        $paraLineNumbers[count($motionParas)] = $lineNumber;
        return $paraLineNumbers;
    }

    /**
     * Returns a hashmap of changed paragraphs.
     * The paragraphs are returned as object including additional information about the line numbers etc.
     *
     * @param SectionedParagraph[] $origParagraphs
     * @param bool $splitListItems
     * @return MotionSectionParagraphAmendment[]
     */
    public function diffDataToOrigParagraphs(array $origParagraphs, bool $splitListItems = true): array
    {
        /*
        $cached = $this->getCacheItem('diffDataToOrigParagraphs');
        if ($cached !== null && false) {
            return $cached;
        }
        */

        $firstLine  = $this->getFirstLineNumber();
        $lineLength = $this->getMotion()->getMyConsultation()->getSettings()->lineLength;

        $amParagraphs = [];
        $newSections = HTMLTools::sectionSimpleHTML($this->data, $splitListItems);
        $diff         = new Diff();
        $diffParas    = $diff->compareHtmlParagraphs($origParagraphs, $newSections, DiffRenderer::FORMATTING_CLASSES);

        foreach ($diffParas as $paraNo => $diffPara) {
            $firstDiffPos = DiffRenderer::paragraphContainsDiff($diffPara);
            if ($firstDiffPos !== null) {
                $unchanged             = mb_substr($diffPara, 0, $firstDiffPos);
                $lines                 = LineSplitter::countMotionParaLines($unchanged, $lineLength);
                $firstDiffLine         = $firstLine + $lines - 1;
                $amSec                 = new MotionSectionParagraphAmendment(
                    $this->amendmentId,
                    $this->sectionId,
                    $paraNo,
                    $diffPara,
                    $firstDiffLine
                );
                $amParagraphs[$paraNo] = $amSec;
            }
            if (count($origParagraphs) > 0) {
                // $origParagraphs can be empty if the original motion is completely empty
                $firstLine += LineSplitter::countMotionParaLines($origParagraphs[$paraNo]->html, $lineLength);
            }
        }
        /*
        $this->setCacheItem('diffDataToOrigParagraphs', $amParagraphs);
        */
        return $amParagraphs;
    }

    /**
     * Returns a hashmap of changed paragraphs. Only the actual diff-string is returned.
     *
     * @param SectionedParagraph[] $origParagraphs
     * @return string[]
     */
    public function diffStrToOrigParagraphs(array $origParagraphs, bool $splitListItems, int $formatting): array
    {
        $amParagraphs = [];
        $newSections = HTMLTools::sectionSimpleHTML($this->data, $splitListItems);
        $diff         = new Diff();
        $diffParas = $diff->compareHtmlParagraphs($origParagraphs, $newSections, $formatting);
        foreach ($diffParas as $paraNo => $diffPara) {
            $firstDiffPos = DiffRenderer::paragraphContainsDiff($diffPara);
            if ($firstDiffPos !== null) {
                $amParagraphs[$paraNo] = $diffPara;
            }
        }
        return $amParagraphs;
    }

    /**
     * @return string[]
     */
    public function getParagraphsRelativeToOriginal(bool $splitListItems = true): array
    {
        $newSections = HTMLTools::sectionSimpleHTML($this->data, $splitListItems);
        $oldSections = HTMLTools::sectionSimpleHTML($this->getOriginalMotionSection()->getData(), $splitListItems);
        return ArrayMatcher::computeMatchingAffectedParagraphs($oldSections, $newSections);
    }

    /**
     * @param string[] $overrides
     * @throws Internal
     */
    public function canRewrite(string $newMotionHtml, array $overrides = []): bool
    {
        if ($this->getSettings()->type !== ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Rewriting is only possible for simple text');
        }
        $oldMotionHtml = $this->getOriginalMotionSection()->getData();
        return AmendmentRewriter::canRewrite($oldMotionHtml, $newMotionHtml, $this->data, $overrides);
    }

    /**
     * @return array<array{text: string, amendmentDiff: string, motionNewDiff: string, lineFrom?: int, lineTo?: int}>
     */
    public function getRewriteCollisions(string $newMotionHtml, bool $asDiff = false, bool $debug = false): array
    {
        if ($this->getSettings()->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Rewriting is only possible for simple text');
        }
        $oldMotionHtml = $this->getOriginalMotionSection()->getData();
        return AmendmentRewriter::getCollidingParagraphs(
            $oldMotionHtml,
            $newMotionHtml,
            $this->data,
            $asDiff,
            $this->getParagraphLineNumberHelper(),
            $debug
        );
    }

    /**
     * @param string[] $overrides
     */
    public function performRewrite(string $newMotionHtml, array $overrides = []): void
    {
        $oldMotionHtml = $this->getOriginalMotionSection()->getData();
        $this->data    = AmendmentRewriter::performRewrite($oldMotionHtml, $newMotionHtml, $this->data, $overrides);
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        $this->data = $data;
    }
}
