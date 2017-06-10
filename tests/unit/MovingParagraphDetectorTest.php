<?php

namespace unit;

use app\components\diff\MovingParagraphDetector;
use app\models\db\Motion;
use app\models\db\MotionSection;

class MovingParagraphDetectorTest extends DBTestBase
{
    /**
     */
    public function testTest1()
    {
        $diffParas = [
            '<p>A paragraph with no changes</p>',
            '<p>Another paragraph</p><p class="inserted">the paragraph to be moved</p>',
            '<p>Test</p>',
            '<p class="deleted">the paragraph to be moved</p>',
        ];
        $markedUp  = MovingParagraphDetector::markupMovedParagraphs($diffParas);
        $this->assertEquals([
            '<p>A paragraph with no changes</p>',
            '<p>Another paragraph</p><p data-moving-partner-id="1" data-moving-partner-paragraph="3" class="inserted moved">the paragraph to be moved</p>',
            '<p>Test</p>',
            '<p data-moving-partner-id="1" data-moving-partner-paragraph="1" class="deleted moved">the paragraph to be moved</p>',
        ], $markedUp);
    }

    /**
     */
    public function testTest2()
    {
        $movedParagraphInserted = [];
        $movedParagraphDeleted = [];

        /** @var Motion $motion */
        $motion = Motion::findOne(117);
        /** @var MotionSection $section */
        foreach ($motion->sections as $section) {
            if ($section->sectionId == 2) {
                $amendments = [];
                foreach ($motion->getVisibleAmendments() as $amendment) {
                    $amendments[] = $amendment->id;
                }
                $merger     = $section->getAmendmentDiffMerger($amendments);
                $paragraphs = $section->getTextParagraphObjects(false, false, false);
                foreach (array_keys($paragraphs) as $paragraphNo) {
                    $groupedParaData = $merger->getGroupedParagraphData($paragraphNo);
                    foreach ($groupedParaData as $group) {
                        if (mb_strpos($group['text'], 'data-moving-partner-id') !== false) {
                            if (mb_strpos($group['text'], '###INS_START###') !== false) {
                                $movedParagraphInserted[] = $paragraphNo;
                            }
                            if (mb_strpos($group['text'], '###DEL_START###') !== false) {
                                $movedParagraphDeleted[] = $paragraphNo;
                            }
                        }
                    }
                }
            }
        }
        $this->assertEquals([1, 6], $movedParagraphDeleted);
        $this->assertEquals([3, 11], $movedParagraphInserted);
    }
}
