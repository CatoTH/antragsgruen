<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\db\Amendment;
use app\models\sectionTypes\ISectionType;

class SingleAmendmentMergeViewParagraphData
{
    /** @var int */
    public $lineFrom;
    /** @var int */
    public $lineTo;
    /** @var string */
    public $plain;
    /** @var string */
    public $diff;
    /** @var string */
    public $modDiff;
    /** @var string */
    public $modPlain;

    /**
     * @param int $lineFrom
     * @param int $lineTo
     * @param string $plain
     * @param string $diff
     * @param string $modPlain
     * @param string $modDiff
     */
    public function __construct($lineFrom, $lineTo, $plain, $diff, $modPlain, $modDiff)
    {
        $this->lineFrom = $lineFrom;
        $this->lineTo   = $lineTo;
        $this->plain    = $plain;
        $this->diff     = $diff;
        $this->modDiff  = $modDiff;
        $this->modPlain = $modPlain;
    }

    /**
     * @param Amendment $amendment
     * @return SingleAmendmentMergeViewParagraphData[][]
     */
    public static function createFromAmendment(Amendment $amendment)
    {
        $paragraphSections = [];
        $diffRenderer      = new DiffRenderer();
        $diffRenderer->setFormatting(DiffRenderer::FORMATTING_CLASSES);

        $modifiedSections = [];
        if ($amendment->hasAlternativeProposaltext(false)) {
            /** @var Amendment $modifiedAmend */
            $modifiedAmend = $amendment->getAlternativeProposaltextReference()['modification'];
            foreach ($modifiedAmend->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
                $modifiedSections[$section->sectionId] = $section;
            }
        }

        foreach ($amendment->getActiveSections(ISectionType::TYPE_TEXT_SIMPLE) as $section) {
            $motionParas     = HTMLTools::sectionSimpleHTML($section->getOriginalMotionSection()->getData());
            $amendmentParas  = HTMLTools::sectionSimpleHTML($section->data);
            $paragraphsDiff  = AmendmentRewriter::computeAffectedParagraphs($motionParas, $amendmentParas, true);
            $paragraphsPlain = AmendmentRewriter::computeAffectedParagraphs($motionParas, $amendmentParas, false);

            $modifiedParas = [];
            $modifiedPlain = [];
            if (isset($modifiedSections[$section->sectionId])) {
                $modAmendParas = HTMLTools::sectionSimpleHTML($modifiedSections[$section->sectionId]->data);
                $modifiedParas = AmendmentRewriter::computeAffectedParagraphs($motionParas, $modAmendParas, true);
                $modifiedPlain = AmendmentRewriter::computeAffectedParagraphs($motionParas, $modAmendParas, false);
            }

            $affectedParas = array_unique(array_merge(array_keys($paragraphsDiff), array_keys($modifiedParas)));
            sort($affectedParas);

            $paraLineNumbers = $section->getParagraphLineNumberHelper();
            $paragraphs      = [];
            foreach ($affectedParas as $paraNo) {
                $paragraph    = (isset($paragraphsDiff[$paraNo]) ? $paragraphsDiff[$paraNo] : null);
                $modifiedPara = (isset($modifiedParas[$paraNo]) ? $modifiedParas[$paraNo] : null);

                if ($paragraph) {
                    $paraDiff  = $diffRenderer->renderHtmlWithPlaceholders($paragraphsDiff[$paraNo]);
                    $paraPlain = $paragraphsPlain[$paraNo];
                } else {
                    // The original amendment should always be filled with data.
                    // This case is necessary when the modified amendment modifies a paragraph that was not modified
                    // by the original amendment.
                    $paraDiff  = $motionParas[$paraNo];
                    $paraPlain = $motionParas[$paraNo];
                }

                if ($modifiedPara && $modifiedPara !== $paragraph) {
                    $modDiff  = $diffRenderer->renderHtmlWithPlaceholders($modifiedParas[$paraNo]);
                    $modPlain = $modifiedPlain[$paraNo];
                } else {
                    $modDiff  = null;
                    $modPlain = null;
                }

                $paragraphs[$paraNo] = new SingleAmendmentMergeViewParagraphData(
                    $paraLineNumbers[$paraNo],
                    $paraLineNumbers[$paraNo + 1] - 1,
                    $paraPlain,
                    $paraDiff,
                    $modPlain,
                    $modDiff
                );
            }

            $paragraphSections[$section->sectionId] = $paragraphs;
        }

        return $paragraphSections;
    }
}
