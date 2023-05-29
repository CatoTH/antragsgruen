<?php

namespace Tests\Unit;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DataTypes\AffectedLineBlock;
use app\components\diff\DiffRenderer;
use app\models\db\Amendment;
use app\models\sectionTypes\TextSimple;
use Codeception\Attribute\Group;
use Codeception\Attribute\Incomplete;
use Tests\Support\Helper\DBTestBase;

#[Group('database')]
class AmendmentLineNumberingTest extends DBTestBase
{
    /**
     * @return AffectedLineBlock[]
     */
    private function getSectionDiff(int $amendmentId, int $sectionId): array
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne($amendmentId);

        $section = null;
        foreach ($amendment->getActiveSections() as $sect) {
            if ($sect->sectionId === $sectionId) {
                $section = $sect;
            }
        }

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($section->getOriginalMotionSection()->getData());
        $formatter->setTextNew($section->data);
        $formatter->setFirstLineNo($section->getFirstLineNumber());
        return $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_CLASSES, 0);
    }

    private function getSectionDiffBlocks(int $amendmentId, int $sectionId): array
    {
        /** @var Amendment $amendment */
        $amendment = Amendment::findOne($amendmentId);

        $section = null;
        foreach ($amendment->getActiveSections() as $sect) {
            if ($sect->sectionId === $sectionId) {
                $section = $sect;
            }
        }
        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($section->getOriginalMotionSection()->getData());
        $formatter->setTextNew($section->data);
        $formatter->setFirstLineNo($section->getFirstLineNumber());
        return $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_CLASSES, 0);
    }

    private static function getAffectedLinesBlock(int $from, int $to, string $text): AffectedLineBlock
    {
        $lines = new AffectedLineBlock();
        $lines->text = $text;
        $lines->lineFrom = $from;
        $lines->lineTo = $to;

        return $lines;
    }

    public function testFirstAffectedLine(): void
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

        /** @var Amendment $amendment */
        $amendment = Amendment::findOne(281);
        $this->assertEquals(24, $amendment->getFirstDiffLine());
    }

    public function testTwoChangesPerLine(): void
    {
        $diff = $this->getSectionDiffBlocks(270, 2);
        $text = '<ul><li value="1">###LINENUMBER###Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?<ins>Abcdsfd#</ins></li></ul><ul class="inserted"><li>Neue Zeile</li></ul>';
        $this->assertEquals([
            self::getAffectedLinesBlock(9, 9, $text),
            self::getAffectedLinesBlock(14, 14, '<ul><li value="1">###LINENUMBER###<del>Woibbadinga </del><ins><strong>Woibbadinga</strong> </ins>noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>'),
        ], $diff);
        $this->assertEquals($text, $diff[0]->text);
    }

    public function testSection1(): void
    {
        $diff = $this->getSectionDiff(3, 2);
        $this->assertEquals(9, $diff[0]->lineFrom);
        $this->assertEquals(9, $diff[0]->lineTo);
        $this->assertEquals(14, $diff[1]->lineFrom);
        $this->assertEquals(14, $diff[1]->lineTo);
        $this->assertEquals(31, $diff[2]->lineFrom);
        $this->assertEquals(31, $diff[2]->lineTo);
        $this->assertEquals(35, $diff[3]->lineFrom);
        $this->assertEquals(35, $diff[3]->lineTo);
    }

    public function testSection1Wording(): void
    {
        $diff = $this->getSectionDiff(3, 2);
        $this->assertStringContainsString('Nach Zeile 9 einfügen', TextSimple::formatDiffGroup([$diff[0]]));
        $this->assertStringContainsString('In Zeile 14 löschen', TextSimple::formatDiffGroup([$diff[1]]));
        $this->assertStringContainsString('In Zeile 31 einfügen:', TextSimple::formatDiffGroup([$diff[2]]));
        $this->assertStringContainsString('In Zeile 35 löschen:', TextSimple::formatDiffGroup([$diff[3]]));
    }


    public function testSection2(): void
    {
        $diff = $this->getSectionDiff(3, 4);
        $this->assertEquals(35, $diff[0]->lineFrom);
        $this->assertEquals(35, $diff[0]->lineTo);
        $this->assertEquals(42, $diff[1]->lineFrom);
        $this->assertEquals(42, $diff[1]->lineTo);
        $this->assertEquals(49, $diff[2]->lineFrom);
        $this->assertEquals(53, $diff[2]->lineTo);
    }

    public function testSection2Wording(): void
    {
        $diff = $this->getSectionDiff(3, 4);
        $this->assertStringContainsString('Vor Zeile 36 einfügen', TextSimple::formatDiffGroup([$diff[0]], '', '', 36));
        $this->assertStringContainsString('Nach Zeile 42 einfügen', TextSimple::formatDiffGroup([$diff[1]], '', '', 36));
        $this->assertStringContainsString('Von Zeile 49 bis 53 löschen:', TextSimple::formatDiffGroup([$diff[2]], '', '', 36));
    }

    public function testInvisibleSpaces(): void
    {
        $in     = [self::getAffectedLinesBlock(16, 16, '###LINENUMBER###Test<del> </del>Bla<ins> </ins>')];
        $expect = '<h4 class="lineSummary">In Zeile 16:</h4><div><p>' .
            'Test<del class="space" aria-label="Streichen: „Leerzeichen”">[Leerzeichen]</del>Bla<ins class="space" aria-label="Einfügen: „Leerzeichen”">[Leerzeichen]</ins>' . '</p></div>';

        $filtered = TextSimple::formatDiffGroup($in);
        $this->assertEquals($expect, $filtered);

        $in     = [self::getAffectedLinesBlock(16, 16, '###LINENUMBER###Test<del><br></del>Bla<ins><br></ins>')];
        $expect = '<h4 class="lineSummary">In Zeile 16:</h4><div><p>Test<del class="space" aria-label="Streichen: „Leerzeichen”">[Zeilenumbruch]</del>' .
            '<del><br></del>Bla<ins class="space" aria-label="Einfügen: „Zeilenumbruch”">[Zeilenumbruch]</ins><ins><br></ins></p></div>';

        $filtered = TextSimple::formatDiffGroup($in);
        $this->assertEquals($expect, $filtered);
    }

    #[Incomplete('TODO')]
    public function testComplicatedParagraphReplace(): void
    {
        $diff         = $this->getSectionDiff(271, 21);
        $expectedDiff = 'selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. <del>Auch werden wir </del><del>demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der </del><del>Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU </del><del>kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen </del><del>Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP </del><del>voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, </del><del>wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird.</del><ins>Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die###FORCELINEBREAK###gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir###FORCELINEBREAK###sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese###FORCELINEBREAK###Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen###FORCELINEBREAK###werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung###FORCELINEBREAK###der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die###FORCELINEBREAK###Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen.</ins>###FORCELINEBREAK###';

        $this->assertEquals([[
            'text'     => $expectedDiff,
            'lineFrom' => 11,
            'lineTo'   => 17,
        ]], $diff);
    }
}
