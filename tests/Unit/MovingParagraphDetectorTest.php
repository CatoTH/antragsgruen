<?php

namespace Tests\Unit;

use app\components\diff\MovingParagraphDetector;
use app\models\db\Motion;
use Codeception\Attribute\Group;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class MovingParagraphDetectorTest extends DBTestBase
{
    public function testTest1(): void
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

    public function testTest2(): void
    {
        $movedParagraphInserted = [];
        $movedParagraphDeleted = [];

        /** @var Motion $motion */
        $motion = Motion::findOne(117);
        foreach ($motion->getActiveSections() as $section) {
            if ($section->sectionId === 2) {
                $amendments = [];
                foreach ($motion->getVisibleAmendments() as $amendment) {
                    $amendments[] = $amendment->id;
                }
                $merger     = $section->getAmendmentDiffMerger($amendments);
                $paragraphs = $section->getTextParagraphObjects(false, false, false);
                foreach (array_keys($paragraphs) as $paragraphNo) {
                    $groupedParaData = $merger->getGroupedParagraphData($paragraphNo);
                    foreach ($groupedParaData as $group) {
                        if (str_contains($group->text, 'data-moving-partner-id')) {
                            if (str_contains($group->text, '###INS_START###')) {
                                $movedParagraphInserted[] = $paragraphNo;
                            }
                            if (str_contains($group->text, '###DEL_START###')) {
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
