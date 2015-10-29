<?php

namespace unit;

use app\components\diff\AmendmentSectionFormatter;
use app\models\db\Amendment;
use app\models\sectionTypes\TextSimple;
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

    /**
     * @param int $amendmentId
     * @param int $sectionId
     * @return array
     */
    private function getSectionDiffBlocks($amendmentId, $sectionId)
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
        return $formatter->getDiffLinesWithNumbers();
    }

    /**
     */
    public function testComplicatedParagraphReplace()
    {
        $diff         = $this->getSectionDiff(271, 21);
        $expectedDiff = 'selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. <del>Auch werden wir </del><br><del>demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der </del><br><del>Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU </del><br><del>kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen </del><br><del>Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP </del><br><del>voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, </del><br><del>wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.</del><br><ins>Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die###FORCELINEBREAK###gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir###FORCELINEBREAK###sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese###FORCELINEBREAK###Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen###FORCELINEBREAK###werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung###FORCELINEBREAK###der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die###FORCELINEBREAK###Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.</ins>';

        $this->assertEquals([[
            'text'     => $expectedDiff,
            'lineFrom' => 11,
            'lineTo'   => 17,
            'newLine'  => false,
        ]], $diff);
    }

    /**
     */
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
     */
    public function testTwoChangesPerLine()
    {
        $diff = $this->getSectionDiffBlocks(270, 2);
        $text = '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand ' .
            'Mongdratzal! Jo leck mi Mamalad i daad mechad?<ins>Abcdsfd#</ins></li></ul>';
        $this->assertEquals([[
            'text'     => $text,
            'lineFrom' => 8,
            'lineTo'   => 9,
            'newLine'  => false,
        ], [
            'text'     => '<ul class="inserted"><li>Neue Zeile</li></ul>',
            'lineFrom' => 9,
            'lineTo'   => 9,
            'newLine'  => true,
        ]], $diff);
        $this->assertEquals($text, $diff[0]['text']);
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
            ], [
                'text'     => '<del>is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>',
                'lineFrom' => 20,
                'lineTo'   => 20,
                'newLine'  => false,
            ],
        ];
        $filtered = AmendmentSectionFormatter::groupAffectedDiffBlocks($in);
        $this->assertContains('Von Zeile 16 bis 18 löschen', TextSimple::formatDiffGroup($filtered));
        $this->assertContains('In Zeile 20 löschen', TextSimple::formatDiffGroup($filtered));
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

    /**
     */
    public function testInvisibleSpaces()
    {
        $in     = [
            [
                'text'     => 'Test<del> </del>Bla<ins> </ins>',
                'lineFrom' => 16,
                'lineTo'   => 16,
                'newLine'  => false,
            ],
        ];
        $expect = '<h4 class="lineSummary">In Zeile 16:</h4><div><p>' .
            'Test<del class="space">[Leerzeichen]</del>Bla<ins class="space">[Leerzeichen]</ins>' . '</p></div>';

        $filtered = TextSimple::formatDiffGroup($in);
        $this->assertEquals($expect, $filtered);

        $in     = [
            [
                'text'     => 'Test<del>###FORCELINEBREAK###</del>Bla<ins>###FORCELINEBREAK###</ins>',
                'lineFrom' => 16,
                'lineTo'   => 16,
                'newLine'  => false,
            ],
        ];
        $expect = '<h4 class="lineSummary">In Zeile 16:</h4><div><p>Test<del class="space">[Zeilenumbruch]</del>' .
            '<del>###FORCELINEBREAK###</del>Bla<ins class="space">[Zeilenumbruch]</ins><ins>###FORCELINEBREAK###</ins></p></div>';

        $filtered = TextSimple::formatDiffGroup($in);
        $this->assertEquals($expect, $filtered);
    }

}
