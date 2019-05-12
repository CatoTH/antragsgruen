<?php

namespace app\components\diff\amendmentMerger;

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

        $this->paragraphs     = [];
        foreach ($paras as $paraNo => $paraStr) {
            $this->paragraphs[$paraNo]     = new ParagraphMerger($paraStr);
        }
    }

    /**
     * @param int $amendmentId
     * @param string[] $amendingParas
     */
    public function addAmendingParagraphs($amendmentId, $amendingParas)
    {
        $diff     = new Diff();
        $amParams = ['amendmentId' => $amendmentId];
        $paraArr  = $diff->compareHtmlParagraphsToWordArray($this->paragraphStrings, $amendingParas, $amParams);
        $paraArr  = MovingParagraphDetector::markupWordArrays($paraArr);

        foreach ($paraArr as $paraNo => $wordArr) {
            $this->paragraphs[$paraNo]->addAmendmentParagraph($amendmentId, $wordArr);
        }
    }

    /**
     * @param AmendmentSection[] $sections
     */
    public function addAmendingSections($sections)
    {
        foreach ($sections as $section) {
            $newParas = HTMLTools::sectionSimpleHTML($section->data);
            $this->addAmendingParagraphs($section->amendmentId, $newParas);
        }
    }

    /**
     * @param int $paraNo
     * @return array
     */
    public function getGroupedParagraphData($paraNo)
    {
        return $this->paragraphs[$paraNo]->getGroupedParagraphData();
    }

    /**
     * @param int $paraNo
     * @return array
     */
    public function getCollidingParagraphGroups($paraNo)
    {
        return $this->paragraphs[$paraNo]->getCollidingParagraphGroups();
    }

    /**
     * @param int $paraNo
     * @return int[]
     */
    public function getAffectingAmendmentIds($paraNo)
    {
        return $this->paragraphs[$paraNo]->getAffectingAmendmentIds();
    }

    /**
     * @return boolean
     */
    public function hasCollidingParagraphs()
    {
        foreach ($this->paragraphs as $paragraph) {
            if (count($paragraph->getCollidingParagraphs()) > 0) {
                return true;
            }
        }
        return false;
    }
}
