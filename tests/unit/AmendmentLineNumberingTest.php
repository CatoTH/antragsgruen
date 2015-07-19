<?php

namespace unit;

use app\components\diff\AmendmentSectionFormatter;
use app\models\db\Amendment;
use app\models\sectionTypes\ISectionType;
use app\models\sectionTypes\TextSimple;
use Codeception\Specify;

class AmendmentLineNumberingTest extends DBTestBase
{
    public function testFirstAffectedLine()
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(1);
        $this->assertEquals(14, $amendment->getFirstDiffLine());

        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(2);
        $this->assertEquals(1, $amendment->getFirstDiffLine());

        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(3);
        $this->assertEquals(9, $amendment->getFirstDiffLine());

    }

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

    /**
     */
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

    /**
     */
    public function testSection1Wording()
    {
        $diff = $this->getSectionDiff(3, 2);
        $this->assertContains('Nach Zeile 9 einfügen', TextSimple::formatDiffGroup([$diff[0]]));
        $this->assertContains('In Zeile 14 löschen', TextSimple::formatDiffGroup([$diff[1]]));
        $this->assertContains('In Zeile 31 einfügen:', TextSimple::formatDiffGroup([$diff[2]]));
        $this->assertContains('In Zeile 35 löschen:', TextSimple::formatDiffGroup([$diff[3]]));
    }

    /**
     */
    public function testGroupAffectedLines()
    {
        $in     = [
            [
                'text'     => '<del>Leonhardifahrt ma da middn. Greichats an naa do.</del>',
                'lineFrom' => 16,
                'lineTo'   => 16,
                'newLine'  => false,
            ], [
                'text'     => '<del>Marei, des um Godds wujn Biakriagal!</del>',
                'lineFrom' => 17,
                'lineTo'   => 17,
                'newLine'  => false,
            ], [
                'text'     => '<del>is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>',
                'lineFrom' => 18,
                'lineTo'   => 18,
                'newLine'  => false,
            ],
        ];
        $expect = [
            [
                'text'     => '<del>Leonhardifahrt ma da middn. Greichats an naa do.</del><br>' .
                    '<del>Marei, des um Godds wujn Biakriagal!</del><br>' .
                    '<del>is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>',
                'lineFrom' => 16,
                'lineTo'   => 18,
                'newLine'  => false,
            ]
        ];

        $filtered = AmendmentSectionFormatter::groupAffectedDiffBlocks($in);
        $this->assertEquals($expect, $filtered);
    }

    /**
     */
    public function testGroupedWordings()
    {
        $in       = [
            [
                'text'     => '<del>Leonhardifahrt ma da middn. Greichats an naa do.</del>',
                'lineFrom' => 16,
                'lineTo'   => 16,
                'newLine'  => false,
            ], [
                'text'     => '<del>Marei, des um Godds wujn Biakriagal!</del>',
                'lineFrom' => 17,
                'lineTo'   => 17,
                'newLine'  => false,
            ], [
                'text'     => '<del>is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>',
                'lineFrom' => 18,
                'lineTo'   => 18,
                'newLine'  => false,
            ],
        ];
        $filtered = AmendmentSectionFormatter::groupAffectedDiffBlocks($in);
        $this->assertContains('Von Zeile 16 bis 18 löschen', TextSimple::formatDiffGroup($filtered));
    }

    /**
     */
    public function testSection2()
    {
        $diff = $this->getSectionDiff(3, 4);
        $this->assertEquals(35, $diff[0]['lineFrom']);
        $this->assertEquals(35, $diff[0]['lineTo']);
        $this->assertEquals(42, $diff[1]['lineFrom']);
        $this->assertEquals(42, $diff[1]['lineTo']);
        $this->assertEquals(49, $diff[2]['lineFrom']);
        $this->assertEquals(53, $diff[2]['lineTo']);
    }

    /**
     */
    public function testSection2Wording()
    {
        $diff = $this->getSectionDiff(3, 4);
        $this->assertContains('Vor Zeile 36 einfügen', TextSimple::formatDiffGroup([$diff[0]], '', '', 36));
        $this->assertContains('Nach Zeile 42 einfügen', TextSimple::formatDiffGroup([$diff[1]], '', '', 36));
        $this->assertContains('Von Zeile 49 bis 53 löschen:', TextSimple::formatDiffGroup([$diff[2]], '', '', 36));
    }
}
