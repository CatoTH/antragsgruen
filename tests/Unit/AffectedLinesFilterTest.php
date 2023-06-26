<?php

namespace Tests\Unit;

use app\components\diff\AffectedLinesFilter;
use app\components\diff\DataTypes\AffectedLineBlock;
use app\models\sectionTypes\TextSimple;
use Tests\Support\Helper\TestBase;

class AffectedLinesFilterTest extends TestBase
{
    private static function getAffectedLinesBlock(int $from, int $to, string $text): AffectedLineBlock
    {
        $lines = new AffectedLineBlock();
        $lines->text = $text;
        $lines->lineFrom = $from;
        $lines->lineTo = $to;

        return $lines;
    }

    public function testFilterWithContext1(): void
    {
        $diffParas = [
            '<ul><li>###LINENUMBER###Test 1 ' .
            '###LINENUMBER###Test 2.</li></ul>',

            '<ul><li><del>###LINENUMBER###Test 3</del> ' .
            '###LINENUMBER###Test 4 ' .
            '###LINENUMBER###Test 5</li></ul>',
        ];
        $expected  = [
            self::getAffectedLinesBlock(3, 4, '<ul><li>###LINENUMBER###<del>Test 3</del> ###LINENUMBER###Test 4 </li></ul>'),
        ];
        $diff      = implode('', $diffParas);
        $lines     = AffectedLinesFilter::splitToAffectedLines($diff, 1, 1);

        $this->assertEquals($expected, $lines);
    }

    public function testLiSplitIntoTwo(): void
    {
        $diffParas = [
            '<ul><li>###LINENUMBER###Test 1 ' .
            '###LINENUMBER###Test 2.</li></ul>',

            '<ul><li><del>###LINENUMBER###Test 3 ' .
            '###LINENUMBER###Test 4 ' .
            '###LINENUMBER###Test 5</del><p class="inserted">Neuer Text.</p></li></ul>',
        ];
        $expected  = [
            self::getAffectedLinesBlock(3, 5, '<ul><li>###LINENUMBER###<del>Test 3 ###LINENUMBER###Test 4 ###LINENUMBER###Test 5</del></li></ul>' .
                '<ul><li><p class="inserted">Neuer Text.</p></li></ul>')
        ];
        $diff      = implode('', $diffParas);
        $lines     = AffectedLinesFilter::splitToAffectedLines($diff, 1);

        $this->assertEquals($expected, $lines);
    }

    public function testBasic(): void
    {
        /*
        $orig = '<p>Test Test</p><ul><li>Point 1</li><li>Point 2</li></ul><p>Test</p>';
        $new  = '<p>Test Test2</p><ul><li>Point 1</li><li>Point 2</li><li>Test 3</li></ul>';

        $newParagraphs  = HTMLTools::sectionSimpleHTML($new);
        $origParas = HTMLTools::sectionSimpleHTML($orig);
        $origParas = LineSplitter::addLineNumbersToParagraphs($origParagraphs, 80);
        $diff           = new Diff();
        $diffParas = $diff->compareSectionedHtml($origParas, $newParas, DiffRenderer::FORMATTING_CLASSES);
        */
        $diffParas = [
            '<p>###LINENUMBER###Test Test<ins>2</ins></p>',
            '<ul><li>###LINENUMBER###Point 1</li></ul>',
            '<ul><li>###LINENUMBER###Point 2</li></ul>',
            '<p class="deleted">###LINENUMBER###Test</p><ul class="inserted"><li>Test 3</li></ul>',
        ];
        $expected  = [
            self::getAffectedLinesBlock(1, 1, '<p>###LINENUMBER###Test Test<ins>2</ins></p>'),
            self::getAffectedLinesBlock(4, 4, '<p class="deleted">###LINENUMBER###Test</p><ul class="inserted"><li>Test 3</li></ul>'),
        ];
        $diff      = implode('', $diffParas);
        $lines     = AffectedLinesFilter::splitToAffectedLines($diff, 1);

        $this->assertEquals($expected, $lines);
    }

    public function testUlLiInserted(): void
    {

        // 'Inserted LIs should be shown'
        $in     = '<ul class="inserted"><li>Oamoi a Maß und no a Maß</li></ul>';
        $expect = [
            self::getAffectedLinesBlock(0, 0, '<ul class="inserted"><li>Oamoi a Maß und no a Maß</li></ul>')
        ];
        $out    = AffectedLinesFilter::splitToAffectedLines($in, 1);
        $this->assertEquals($expect, $out);
    }

    public function testUlLiWithLineBreaks(): void
    {
        // 'Line breaks within lists'

        $in = '<p>###LINENUMBER###Do nackata Wurscht i hob di ' .
            '###LINENUMBER###narrisch gean, Diandldrahn Deandlgwand vui ' .
            '###LINENUMBER###Do nackata</p>' . "\n" .
            '<ul><li>###LINENUMBER###Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand ###LINENUMBER###huift vui woaß?</li></ul>' . "\n" .
            '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>';

        $expect = [
            self::getAffectedLinesBlock(5, 5, '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>'),
        ];

        $out = AffectedLinesFilter::splitToAffectedLines($in, 1);

        $this->assertEquals($expect, $out);
    }

    public function testUlLiInlineFormatted(): void
    {
        $in     = '<div style="color:#FF0000; margin: 0; padding: 0;"><ul class="deleted"><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul></div>';
        $expect = [
            self::getAffectedLinesBlock(1, 1, '<div style="color:#FF0000;margin:0;padding:0;"><ul class="deleted"><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul></div>')
        ];

        $out = AffectedLinesFilter::splitToAffectedLines($in, 1);

        $this->assertEquals($expect, $out);
    }


    public function testMultilineParagraph(): void
    {
        $diff           = '<p class="deleted">###LINENUMBER###Leonhardifahrt ma da middn. Greichats an naa do. Af Schuabladdla Leonhardifahrt ###LINENUMBER###Marei, des um Godds wujn Biakriagal! Hallelujah sog i, luja schüds nei koa des ###LINENUMBER###is schee jedza hogg di hera dringma aweng Spezi nia Musi. Wurschtsolod jo mei is ###LINENUMBER###des schee gor Ramasuri ozapfa no gfreit mi i hob di liab auffi, Schbozal. Hogg ###LINENUMBER###di hera nia need Biakriagal so schee, Schdarmbeaga See.</p>';
        $affectedBlocks = AffectedLinesFilter::splitToAffectedLines($diff, 1);
        $expected       = [self::getAffectedLinesBlock(1, 5, $diff)];
        $this->assertEquals($expected, $affectedBlocks);
    }


    public function testInsertedLi(): void
    {
        $htmlDiff       = '<ul><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul><ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>';
        $affectedBlocks = AffectedLinesFilter::splitToAffectedLines($htmlDiff, 1);
        $expected       = [
            self::getAffectedLinesBlock(1, 1, '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>'),
        ];
        $this->assertEquals($expected, $affectedBlocks);
    }

    public function testFilterAffected(): void
    {
        $lines    = [
            self::getAffectedLinesBlock(1, 1, '###LINENUMBER###Test Test<ins>2</ins>'),
        ];
        $expected = [
            self::getAffectedLinesBlock(1, 1, '###LINENUMBER###Test Test<ins>2</ins>'),
        ];
        $filtered = AffectedLinesFilter::filterAffectedBlocks($lines);
        $this->assertEquals($expected, $filtered);


        $lines    = [
            self::getAffectedLinesBlock(1, 1, '###LINENUMBER###Test Test<ins>2'),
            self::getAffectedLinesBlock(2, 2, '###LINENUMBER###Test Test2'),
            self::getAffectedLinesBlock(3, 3, '###LINENUMBER###Test Test2</ins>'),
            self::getAffectedLinesBlock(4, 4, '###LINENUMBER###Test Test2'),
        ];
        $expected = [
            self::getAffectedLinesBlock(1, 1, '###LINENUMBER###Test Test<ins>2'),
            self::getAffectedLinesBlock(2, 2, '###LINENUMBER###Test Test2'),
            self::getAffectedLinesBlock(3, 3, '###LINENUMBER###Test Test2</ins>'),
        ];
        $filtered = AffectedLinesFilter::filterAffectedBlocks($lines);
        $this->assertEquals($expected, $filtered);
    }

    public function testGroupAffectedLines(): void
    {
        $filtered = AffectedLinesFilter::groupAffectedDiffBlocks([
            self::getAffectedLinesBlock(16, 16, '<del>###LINENUMBER###Leonhardifahrt ma da middn. Greichats an naa do. </del>'),
            self::getAffectedLinesBlock(17, 17, '<del>###LINENUMBER###Marei, des um Godds wujn Biakriagal! </del>'),
            self::getAffectedLinesBlock(18, 18, '<del>###LINENUMBER###is schee jedza hogg di hera dringma aweng Spezi nia Musi. </del>'),
        ]);

        $expectedPart = self::getAffectedLinesBlock(
            16,
            18,
            '<del>###LINENUMBER###Leonhardifahrt ma da middn. Greichats an naa do. </del>' .
            '<del>###LINENUMBER###Marei, des um Godds wujn Biakriagal! </del>' .
            '<del>###LINENUMBER###is schee jedza hogg di hera dringma aweng Spezi nia Musi. </del>'
        );
        $this->assertEquals([$expectedPart], $filtered);
    }

    public function testGroupedWordings(): void
    {
        $filtered = AffectedLinesFilter::groupAffectedDiffBlocks([
            self::getAffectedLinesBlock(16, 16, '<del>###LINENUMBER###Leonhardifahrt ma da middn. Greichats an naa do.</del>'),
            self::getAffectedLinesBlock(17, 17, '<del>###LINENUMBER###Marei, des um Godds wujn Biakriagal!</del>'),
            self::getAffectedLinesBlock(18, 18, '<del>###LINENUMBER###is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>'),
            self::getAffectedLinesBlock(20, 20, '<del>###LINENUMBER###is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>'),
        ]);

        $this->assertStringContainsString('Von Zeile 16 bis 18 löschen', TextSimple::formatDiffGroup($filtered));
        $this->assertStringContainsString('In Zeile 20 löschen', TextSimple::formatDiffGroup($filtered));
    }

    public function testNestedLists(): void
    {
        $diffParas = [
            '<p>###LINENUMBER###Test Test<ins>2</ins></p>',
            '<ul><li><p>###LINENUMBER###Point <del>1</del><ins>2</ins></p><ul><li>###LINENUMBER###Nested 1</li><li><ins>Nested 2</ins></li></ul></li></ul>',
            '<ul><li>###LINENUMBER###Point 2</li></ul>',
            '<ul><li>###LINENUMBER###Test 3</li></ul>',
            '<p class="deleted">###LINENUMBER###Test</p>'
        ];
        $expected  = [
            self::getAffectedLinesBlock(1, 2, '<p>###LINENUMBER###Test Test<ins>2</ins></p><ul><li><p>###LINENUMBER###Point <del>1</del><ins>2</ins></p></li></ul>'),
            self::getAffectedLinesBlock(3, 3, '<ul><li><ul><li><ins>Nested 2</ins></li></ul></li></ul>'),
            self::getAffectedLinesBlock(6, 6, '<p class="deleted">###LINENUMBER###Test</p>'),
        ];
        $diff      = implode('', $diffParas);
        $lines     = AffectedLinesFilter::splitToAffectedLines($diff, 1);
        $this->assertEquals($expected, $lines);
    }

    public function testFilterAffectedBlocks1(): void
    {
        $in       = [
            self::getAffectedLinesBlock(14, 14, 'Gipfe Servas des wiad a Mordsgaudi'),
            self::getAffectedLinesBlock(15, 15, 'Gipfe Servas des wiad a Mordsgaudi'),
            self::getAffectedLinesBlock(16, 16, '<del>Leonhardifahrt ma da middn. Greichats an naa do.'),
            self::getAffectedLinesBlock(17, 17, 'Marei, des um Godds wujn Biakriagal!'),
            self::getAffectedLinesBlock(18, 1, 'is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>'),
        ];
        $expect   = [
            self::getAffectedLinesBlock(16, 16, '<del>Leonhardifahrt ma da middn. Greichats an naa do.'),
            self::getAffectedLinesBlock(17, 17, 'Marei, des um Godds wujn Biakriagal!'),
            self::getAffectedLinesBlock(18, 1, 'is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>'),
        ];
        $filtered = AffectedLinesFilter::filterAffectedBlocks($in, 0);
        $this->assertEquals($expect, $filtered);
    }

    public function testFilterAffectedBlocks2(): void
    {
        $in       = [
            self::getAffectedLinesBlock(15, 15, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(16, 16, 'Bla 1.'),
            self::getAffectedLinesBlock(17, 17, 'Bla 2.'),
            self::getAffectedLinesBlock(18, 18, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
        ];
        $expect   = [
            self::getAffectedLinesBlock(15, 15, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(18, 18, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
        ];
        $filtered = AffectedLinesFilter::filterAffectedBlocks($in);
        $this->assertEquals($expect, $filtered);
    }

    public function testFilterAffectedBlocks3(): void
    {
        $in       = [
            self::getAffectedLinesBlock(15, 15, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(16, 16, 'Bla 1.'),
            self::getAffectedLinesBlock(17, 17, 'Bla 2.'),
            self::getAffectedLinesBlock(18, 18, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(19, 19, 'Bla 2.'),
            self::getAffectedLinesBlock(20, 20, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
        ];
        $expect   = [
            self::getAffectedLinesBlock(15, 15, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(18, 18, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(20, 20, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
        ];
        $filtered = AffectedLinesFilter::filterAffectedBlocks($in, 0);
        $this->assertEquals($expect, $filtered);
    }

    public function testFilterAffectedBlocks4(): void
    {
        $in       = [
            self::getAffectedLinesBlock(15, 15, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(16, 16, 'Bla 1.'),
            self::getAffectedLinesBlock(17, 17, 'Bla 2.'),
            self::getAffectedLinesBlock(18, 18, 'Bla 3.'),
            self::getAffectedLinesBlock(19, 19, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(20, 20, 'Bla 2.'),
            self::getAffectedLinesBlock(21, 21, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
        ];
        $expect   = [
            self::getAffectedLinesBlock(15, 15, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(16, 16, 'Bla 1.'),
            self::getAffectedLinesBlock(18, 18, 'Bla 3.'),
            self::getAffectedLinesBlock(19, 19, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
            self::getAffectedLinesBlock(20, 20, 'Bla 2.'),
            self::getAffectedLinesBlock(21, 21, 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>'),
        ];
        $filtered = AffectedLinesFilter::filterAffectedBlocks($in, 1);
        $this->assertEquals($expect, $filtered);
    }

    public function testFilterAffectedBlocks5(): void
    {
        $in       = [
            self::getAffectedLinesBlock(14, 14, 'Gipfe Servas des wiad a Mordsgaudi'),
            self::getAffectedLinesBlock(15, 15, 'Gipfe Servas des wiad a Mordsgaudi'),
            self::getAffectedLinesBlock(16, 16, '<del>Leonhardifahrt ma da middn. Greichats an naa do.</del>'),
            self::getAffectedLinesBlock(17, 17, 'Marei, des um Godds wujn Biakriagal!'),
            self::getAffectedLinesBlock(18, 1, 'is schee jedza hogg di hera dringma aweng Spezi nia Musi.'),
        ];
        $expect   = [
            self::getAffectedLinesBlock(15, 15, 'Gipfe Servas des wiad a Mordsgaudi'),
            self::getAffectedLinesBlock(16, 16, '<del>Leonhardifahrt ma da middn. Greichats an naa do.</del>'),
            self::getAffectedLinesBlock(17, 17, 'Marei, des um Godds wujn Biakriagal!'),
        ];
        $filtered = AffectedLinesFilter::filterAffectedBlocks($in, 1);
        $this->assertEquals($expect, $filtered);
    }

    public function testComplex(): void
    {
        $in = '<p>###LINENUMBER###Das Ehegattensplitting steht diesen Zielen im Weg. Es ist <ins>unmodern, denn viele Menschen wollen heute .... Es ist </ins>ungerecht, denn es erlaubt nur ' .
            '###LINENUMBER###einem Teil der Familien, Lebensphasen abzufedern, in denen eine Person weniger oder nichts ' .
            '###LINENUMBER###verdient. Das Ehegattensplitting ist nicht nachhaltig. Alleinerziehende oder Paare, die sich ' .
            '###LINENUMBER###verdient2. Das2 Ehegattensplitting ist nicht nachhaltig. Alleinerziehende oder Paare, die sich ' .
            '###LINENUMBER###den Verzicht auf ein zweites Einkommen nicht leisten können, haben nichts davon<ins>. Vom Ehegattensplitting profitieren.... , werden vom Ehegattensplitting nicht erreicht</ins>. Hinzu' .
            '###LINENUMBER###kommt, dass die mit dem Ehegattensplitting geförderte Arbeitsteilung vor allem für Frauen ' .
            '###LINENUMBER###erhebliche Armutsrisiken birgt und langfristig alles andere als eine Absicherung ist<ins>. Denn das Splitting wirkt sich ... Lebensverlauf ins Gegenteil</ins>. Eine ' .
            '###LINENUMBER###Frau, die keiner oder nur einer geringfügigen Erwerbsarbeit nachgeht und in dieser Zeit ' .
            '###LINENUMBER###zusammen mit ihrem Partner vom Splitting profitiert, steht nach der Scheidung oder Verlust ' .
            '###LINENUMBER###des Partners oft ohne eigene Alterssicherung da. Aus diesen Gründen wollen wir zur individuellen ' .
            '###LINENUMBER###Besteuerung übergehen und das Ehegattensplitting durch eine <strong>gezielte Förderung von Familien ' .
            '###LINENUMBER###mit Kindern und <del>Alleinerziehenden</del></strong><ins>Alleinerziehenden</ins> ersetzen.</p>';

        $expect = [
            self::getAffectedLinesBlock(1, 2, '<p>###LINENUMBER###Das Ehegattensplitting steht diesen Zielen im Weg. Es ist <ins>unmodern, denn viele Menschen wollen heute .... Es ist </ins>ungerecht, denn es erlaubt nur ###LINENUMBER###einem Teil der Familien, Lebensphasen abzufedern, in denen eine Person weniger oder nichts </p>'),
            self::getAffectedLinesBlock(4, 8, '<p>###LINENUMBER###verdient2. Das2 Ehegattensplitting ist nicht nachhaltig. Alleinerziehende oder Paare, die sich ###LINENUMBER###den Verzicht auf ein zweites Einkommen nicht leisten können, haben nichts davon<ins>. Vom Ehegattensplitting profitieren.... , werden vom Ehegattensplitting nicht erreicht</ins>. Hinzu###LINENUMBER###kommt, dass die mit dem Ehegattensplitting geförderte Arbeitsteilung vor allem für Frauen ###LINENUMBER###erhebliche Armutsrisiken birgt und langfristig alles andere als eine Absicherung ist<ins>. Denn das Splitting wirkt sich ... Lebensverlauf ins Gegenteil</ins>. Eine ###LINENUMBER###Frau, die keiner oder nur einer geringfügigen Erwerbsarbeit nachgeht und in dieser Zeit </p>'),
            self::getAffectedLinesBlock(11, 12, '<p>###LINENUMBER###Besteuerung übergehen und das Ehegattensplitting durch eine <strong>gezielte Förderung von Familien ###LINENUMBER###mit Kindern und <del>Alleinerziehenden</del></strong><ins>Alleinerziehenden</ins> ersetzen.</p>'),
        ];
        $out    = AffectedLinesFilter::splitToAffectedLines($in, 1, 1);
        $this->assertEquals($expect, $out);
    }

    public function testInsertBr(): void
    {
        $in     = '<ul><li>###LINENUMBER###ausreichende Angebote der Erwachsenenbildung in der ' .
            '###LINENUMBER###Einwanderungsgesellschaft. Dafür müssen die Mittel für die ' .
            '###LINENUMBER###Erwachsenenbildungsträger verdoppelt werden.</li></ul>' .
            '<ins><br></ins>' .
            '<p class="inserted"><strong>Bildung in den Flüchtlingslagern</strong></p>' .
            '<ins><br></ins>' .
            '<p class="inserted">Bildung für Flüchtende beginnt nicht erst in Deutschland, </p><ins><br></ins>' .
            '<p>###LINENUMBER###<strong>Kommunen als Bildungsort </strong></p>';
        $expect = [
            self::getAffectedLinesBlock(3, 3, '<ins><br></ins><p class="inserted"><strong>Bildung in den Flüchtlingslagern</strong></p><ins><br></ins><p class="inserted">Bildung für Flüchtende beginnt nicht erst in Deutschland, </p><ins><br></ins>')
        ];
        $out    = AffectedLinesFilter::splitToAffectedLines($in, 1);
        $this->assertEquals($expect, $out);
    }
}
