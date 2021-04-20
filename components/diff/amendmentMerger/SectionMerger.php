<?php

namespace app\components\diff\amendmentMerger;

use app\components\diff\DataTypes\GroupedParagraphData;
use app\components\diff\Diff;
use app\components\diff\MovingParagraphDetector;
use app\components\HTMLTools;
use app\models\db\AmendmentSection;
use app\models\db\MotionSection;

class SectionMerger
{
    /** @var ParagraphMerger[] */
    private $paragraphs;

    /** @var string[] */
    private $paragraphStrings;

    /**
     * @param MotionSection $section
     * @throws \app\models\exceptions\Internal
     */
    public function initByMotionSection(MotionSection $section)
    {
        $paras    = $section->getTextParagraphLines();
        $sections = [];
        foreach ($paras as $para) {
            $sections[] = str_replace('###LINENUMBER###', '', implode('', $para));
        }
        $this->initByMotionParagraphs($sections);
    }

    /**
     * @param string[] $paras
     */
    public function initByMotionParagraphs($paras)
    {
        $this->paragraphStrings = $paras;

        $this->paragraphs = [];
        if (count($paras) > 0) {
            foreach ($paras as $paraNo => $paraStr) {
                $this->paragraphs[$paraNo] = new ParagraphMerger($paraStr);
            }
        } else {
            $this->paragraphs[0] = new ParagraphMerger('<p></p>');
        }
    }

    /**
     * @param int $amendmentId
     * @param string[] $amendingParas
     */
    public function addAmendingParagraphs($amendmentId, $amendingParas)
    {
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
     * @return GroupedParagraphData[]
     */
    public function getGroupedParagraphData(int $paraNo): array
    {
        return $this->paragraphs[$paraNo]->getGroupedParagraphData();
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
