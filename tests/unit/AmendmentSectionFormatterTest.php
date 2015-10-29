<?php

namespace unit;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\Diff;
use app\models\sectionTypes\TextSimple;
use Codeception\Specify;

class AmendmentSectionFormatterTest extends TestBase
{
    use Specify;

    public function testInlineFormatting()
    {
        $strPre                  = '<p>###LINENUMBER###Test 123</p>';
        $strPost                 = '<p>###LINENUMBER###Test</p>';
        $computed                = AmendmentSectionFormatter::getHtmlDiffWithLineNumberPlaceholdersInt($strPre, $strPost, Diff::FORMATTING_INLINE, false);
        $blocks                  = AmendmentSectionFormatter::htmlDiff2LineBlocks($computed, 2);
        $getDiffLinesWithNumbers = AmendmentSectionFormatter::filterAffectedBlocks($blocks);
        $this->assertEquals(1, count($getDiffLinesWithNumbers));

        $grouped = AmendmentSectionFormatter::groupAffectedDiffBlocks($getDiffLinesWithNumbers);
        $text = TextSimple::formatDiffGroup($grouped);
        $expect = '<h4 class="lineSummary">In Zeile 2 löschen:</h4><div><p>Test<span style="color: red;"><del> 123</del></span></p></div>';
        $this->assertEquals($expect, $text);
    }

    /**
     */
    public function testNoMessingUpLineNumbers()
    {
        $strPre = '<p>###LINENUMBER###<strong>Anspruch und Ausblick</strong></p>
<p>###LINENUMBER###Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch ###LINENUMBER###weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft ###LINENUMBER###vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und ###LINENUMBER###biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkte ###LINENUMBER###und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die ###LINENUMBER###Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang ###LINENUMBER###hier bleiben werden, diesen Prozess verstärken.</p>
<p>###LINENUMBER###Die Stärkung einer europäischen Identität – ohne die Verwischung historischer ###LINENUMBER###Verantwortung und politischer Kontinuitäten – ist für eine zukünftige ###LINENUMBER###Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen ###LINENUMBER###wird und in der Erinnerungsarbeit aufgegriffen werden muss.</p>
<p>###LINENUMBER###Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. ###LINENUMBER###Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden ###LINENUMBER###Herausforderungen an:</p>';
        $strPost = '<p><strong>Anspruch und Ausblick</strong></p>
<p>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkten und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang hier bleiben werden, diesen Prozess verstärken.</p>
<p>Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>';
        $computed                = AmendmentSectionFormatter::getHtmlDiffWithLineNumberPlaceholdersInt($strPre, $strPost, Diff::FORMATTING_CLASSES, false);

        // Hint: could be further improved, by separating the leading 'n' from the big change block
        $this->assertEquals('<p>###LINENUMBER###<strong>Anspruch und Ausblick</strong></p>
<p>###LINENUMBER###Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch ###LINENUMBER###weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft ###LINENUMBER###vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und ###LINENUMBER###biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkte<del> ###LINENUMBER###und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die ###LINENUMBER###Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang ###LINENUMBER###hier bleiben werden, diesen Prozess verstärken.</del></p>
<p><del>###LINENUMBER###Die Stärkung einer europäischen Identität – ohne die Verwischung historischer ###LINENUMBER###Verantwortung und politischer Kontinuitäten – ist für eine zukünftige ###LINENUMBER###Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen ###LINENUMBER###wird und in der Erinnerungsarbeit aufgegriffen werden muss.</del></p>
<p><del>###LINENUMBER###Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. </del>
<ins>n und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang hier bleiben werden, diesen Prozess verstärken.</p>
</ins><p>###LINENUMBER###Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden ###LINENUMBER###Herausforderungen an:</p>', $computed);
    }

    /**
     *
     */
    public function testUlLiInserted()
    {

        // 'Inserted LIs should be shown'
        $in     = [[
            'text' => '<ul class="inserted"><li>Oamoi a Maß und no a Maß</li></ul>',
        ]];
        $expect = $in;
        $out    = AmendmentSectionFormatter::filterAffectedBlocks($in);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testUlLiWithLineBreaks()
    {
        // 'Line breaks within lists'

        $in = '<p>###LINENUMBER###Do nackata Wurscht i hob di ' .
            '###LINENUMBER###narrisch gean, Diandldrahn Deandlgwand vui ' .
            '###LINENUMBER###Do nackata</p>' . "\n" .
            '<ul><li>###LINENUMBER###Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand ###LINENUMBER###huift vui woaß?</li></ul>' . "\n" .
            '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>';

        $expect = [
            '###LINENUMBER###Do nackata Wurscht i hob di ',
            '###LINENUMBER###narrisch gean, Diandldrahn Deandlgwand vui ',
            '###LINENUMBER###Do nackata',
            '<ul><li>###LINENUMBER###Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand ###LINENUMBER###huift vui woaß?</li></ul>',
            '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>'
        ];

        $out = AmendmentSectionFormatter::getDiffSplitToLines($in);

        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testUlLiInlineFormatted()
    {
        $in = '<div style="color: red; margin: 0; padding: 0;"><ul class="deleted"><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul></div>';
        $expect = [
            '<div style="color: red; margin: 0; padding: 0;"><ul class="deleted"><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul></div>',
        ];

        $out = AmendmentSectionFormatter::getDiffSplitToLines($in);

        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testLineBreaksWithinParagraphs()
    {
        // 'Line breaks within paragraphs'
        $orig = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf maßlose Deregulierung; eine Politik der sozialen Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; eine Politik, die auch unpopuläre Strukturreformen angeht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>[b]Die Kaputtsparpolitik ist gescheitert[/b]<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        $new = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; ein Wirtschaftsmodell, das auch unbequeme Strukturreformen mit einbezieht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>[b]Die Kaputtsparpolitik ist gescheitert[/b]<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        $expect = [
            [
                'text'     => 'Innovationen setzt statt auf <del>maßlose Deregulierung; eine Politik der sozialen</del><ins>Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer</ins> ',
                'lineFrom' => 5,
                'lineTo'   => 5,
                'newLine'  => false,
            ],
            [
                'text'     => 'Spaltung unserer Gesellschaften; ein<del>e Politik, die</del><ins> Wirtschaftsmodell, das</ins> auch un<del>populär</del><ins>bequem</ins>e ',
                'lineFrom' => 7,
                'lineTo'   => 7,
                'newLine'  => false,
            ],
            [
                'text'     => 'Strukturreformen <del>ang</del><ins>mit einbezi</ins>eht, wenn diese zu nachhaltigem Wachstum und mehr ',
                'lineFrom' => 8,
                'lineTo'   => 8,
                'newLine'  => false,
            ],
        ];

        $out = AmendmentSectionFormatter::getDiffLinesWithNumbersDebug($orig, $new);

        $this->assertEquals($expect, $out);

        // @TODO:
        // - <li>s that are changed
        // - <li>s that are deleted
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testFilterAffectedBlocks1()
    {
        $in       = [
            [
                'text'     => 'Gipfe Servas des wiad a Mordsgaudi',
                'lineFrom' => 15,
                'lineTo'   => 15,
                'newLine'  => false,
            ], [
                'text'     => '<del>Leonhardifahrt ma da middn. Greichats an naa do.',
                'lineFrom' => 16,
                'lineTo'   => 16,
                'newLine'  => false,
            ], [
                'text'     => 'Marei, des um Godds wujn Biakriagal!',
                'lineFrom' => 17,
                'lineTo'   => 17,
                'newLine'  => false,
            ], [
                'text'     => 'is schee jedza hogg di hera dringma aweng Spezi nia Musi.</del>',
                'lineFrom' => 18,
                'lineTo'   => 1,
                'newLine'  => false,
            ],
        ];
        $expect   = [
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
                'lineTo'   => 1,
                'newLine'  => false,
            ],
        ];
        $filtered = AmendmentSectionFormatter::filterAffectedBlocks($in);
        $this->assertEquals($expect, $filtered);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testFilterAffectedBlocks2()
    {
        $in       = [
            [
                'text'     => 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>',
                'lineFrom' => 15,
                'lineTo'   => 15,
                'newLine'  => false,
            ], [
                'text'     => 'Bla 1.',
                'lineFrom' => 16,
                'lineTo'   => 16,
                'newLine'  => false,
            ], [
                'text'     => 'Bla 2.',
                'lineFrom' => 17,
                'lineTo'   => 17,
                'newLine'  => false,
            ], [
                'text'     => 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>',
                'lineFrom' => 18,
                'lineTo'   => 18,
                'newLine'  => false,
            ],
        ];
        $expect   = [
            [
                'text'     => 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>',
                'lineFrom' => 15,
                'lineTo'   => 15,
                'newLine'  => false,
            ], [
                'text'     => 'Test1 <del>Test2</del> Test3 <ins>Test4</ins> <del>Test2</del> Test3 <ins>Test4</ins>',
                'lineFrom' => 18,
                'lineTo'   => 18,
                'newLine'  => false,
            ],
        ];
        $filtered = AmendmentSectionFormatter::filterAffectedBlocks($in);
        $this->assertEquals($expect, $filtered);
    }


    /**
     */
    public function testLinesWithoutNumber()
    {
        $in     = '<ins><p>New line at beginning</p></ins>' . "\n" .
            '<p>###LINENUMBER###Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma ###LINENUMBER###sagrisch guad.</p>' . "\n" .
            '<ins><p>Neuer Absatz</p></ins>';
        $expect = [
            '<ins>New line at beginning</ins>',
            '###LINENUMBER###Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma ',
            '###LINENUMBER###sagrisch guad.',
            '<ins>Neuer Absatz</ins>',
        ];
        $out    = AmendmentSectionFormatter::getDiffSplitToLines($in);
        $this->assertEquals($expect, $out);
    }
}
