<?php

namespace unit;

use app\components\diff\AmendmentSectionFormatter;
use app\models\db\Amendment;
use Codeception\Specify;

class AmendmentLineNumberingTest extends DBTestBase
{
    /**
     * @param int $amendmentId
     * @param int $sectionId
     * @return array
     */
    private function getSectionDiff($amendmentId, $sectionId)
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne($amendmentId);

        $section = null;
        foreach ($amendment->sections as $sect) {
            if ($sect->sectionId == $sectionId) {
                $section = $sect;
            }
        }
        $formatter = new AmendmentSectionFormatter($section, \app\components\diff\Diff::FORMATTING_CLASSES);
        return $formatter->getGroupedDiffLinesWithNumbers();
    }

    public function testSection1()
    {
        $diff = $this->getSectionDiff(3, 2);
        $this->assertEquals(9, $diff[0]['lineFrom']);
        $this->assertEquals(9, $diff[0]['lineTo']);
        $this->assertEquals(14, $diff[1]['lineFrom']);
        $this->assertEquals(14, $diff[1]['lineTo']);
        $this->assertEquals(31, $diff[2]['lineFrom']);
        $this->assertEquals(31, $diff[2]['lineTo']);
        $this->assertEquals(35, $diff[3]['lineFrom']);
        $this->assertEquals(35, $diff[3]['lineTo']);
    }
}
