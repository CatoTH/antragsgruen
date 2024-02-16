<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\DataTypes\GroupedParagraphData;
use app\components\diff\{Diff, MovingParagraphDetector};
use app\components\HTMLTools;
use app\models\db\{AmendmentSection, MotionSection};
use app\models\SectionedParagraph;

class SectionMerger
{
    /** @var ParagraphMerger[] */
    private array $paragraphs;

    /** @var string[] */
    private array $paragraphStrings;

    // If set to true, then collisions will be merged into the text, preferring ease of editing over consistency
    private bool $mergeCollisions;

    public function __construct(bool $mergeCollisions = true)
    {
        $this->mergeCollisions = $mergeCollisions;
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function initByMotionSection(MotionSection $section): void
    {
        $sections = array_map(fn(SectionedParagraph $para) => new SectionedParagraph(
            str_replace('###LINENUMBER###', '', implode('', $para->lines)),
            $para->paragraphWithoutLineSplit,
            $para->paragraphWithLineSplit
        ), $section->getTextParagraphLines());

        $this->initByMotionParagraphs($sections);
    }

    /**
     * @param SectionedParagraph[] $paras
     */
    public function initByMotionParagraphs(array $paras): void
    {
        $this->paragraphStrings = array_map(fn(SectionedParagraph $para) => $para->html, $paras);

        $this->paragraphs = [];
        if (count($paras) > 0) {
            foreach ($paras as $para) {
                $this->paragraphs[$para->paragraphWithLineSplit] = new ParagraphMerger($para->html, $this->mergeCollisions);
            }
        } else {
            $this->paragraphs[0] = new ParagraphMerger('<p></p>', $this->mergeCollisions);
        }
    }

    /**
     * @param SectionedParagraph[] $amendingParas
     */
    public function addAmendingParagraphs(int $amendmentId, array $amendingParas): void
    {
        $amendingParas = array_map(fn(SectionedParagraph $par) => $par->html, $amendingParas);

        $diff     = new Diff();
        $paraArr  = $diff->compareHtmlParagraphsToWordArray($this->paragraphStrings, $amendingParas, intval($amendmentId));
        $paraArr  = MovingParagraphDetector::markupWordArrays($paraArr);

        foreach ($paraArr as $paraNo => $wordArr) {
            $this->paragraphs[$paraNo]->addAmendmentParagraph($amendmentId, $wordArr);
        }
    }

    /**
     * @param AmendmentSection[] $sections
     */
    public function addAmendingSections(array $sections): void
    {
        foreach ($sections as $section) {
            $newParas = HTMLTools::sectionSimpleHTML($section->data);
            $this->addAmendingParagraphs($section->amendmentId, $newParas);
        }
    }

    public function getParagraphMerger(int $paraNo): ParagraphMerger
    {
        return $this->paragraphs[$paraNo];
    }

    /**
     * Hint: Only used for tests
     *
     * @return GroupedParagraphData[]
     */
    public function getGroupedParagraphData(int $paraNo): array
    {
        $CHANGESET_COUNTER = 0;
        return $this->paragraphs[$paraNo]->getGroupedParagraphData($CHANGESET_COUNTER);
    }

    /**
     * @return GroupedParagraphData[][]
     */
    public function getCollidingParagraphGroups(int $paraNo): array
    {
        return $this->paragraphs[$paraNo]->getCollidingParagraphGroups();
    }

    /**
     * @return int[]
     */
    public function getAffectingAmendmentIds(int $paraNo): array
    {
        return $this->paragraphs[$paraNo]->getAffectingAmendmentIds();
    }

    public function hasCollidingParagraphs(): bool
    {
        foreach ($this->paragraphs as $paragraph) {
            if (count($paragraph->getCollidingParagraphs()) > 0) {
                return true;
            }
        }
        return false;
    }
}
