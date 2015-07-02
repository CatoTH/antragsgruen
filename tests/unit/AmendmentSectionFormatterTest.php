<?php

namespace unit;

use app\components\diff\AmendmentSectionFormatter;
use Codeception\Specify;
use Codeception\Util\Autoload;

Autoload::addNamespace('unit', __DIR__);

class AmendmentSectionFormatterTest extends TestBase
{
    use Specify;

    /**
     *
     */
    public function testUlLi()
    {
        $this->specify(
            'Inserted LIs should not be shown',
            function () {
                $in     = ['<ul class="inserted"><li>Oamoi a Maß und no a Maß</li></ul>'];
                $expect = ['<ul class="inserted"><li>Oamoi a Maß und no a Maß</li></ul>'];
                $out    = AmendmentSectionFormatter::getDiffLinesWithNumberComputed($in, 0, false);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Line breaks within lists',
            function () {
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
        );

        $this->specify(
            'Line breaks within paragraphs',
            function () {
                $orig = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf maßlose Deregulierung; eine Politik der sozialen Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; eine Politik, die auch unpopuläre Strukturreformen angeht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>[b]Die Kaputtsparpolitik ist gescheitert[/b]<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

                $new = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; ein Wirtschaftsmodell, das auch unbequeme Strukturreformen mit einbezieht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>[b]Die Kaputtsparpolitik ist gescheitert[/b]<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

                $expect = [
                    4 => '<span class="lineNumber" data-line-number="5"></span>Innovationen setzt statt auf <del>maßlose Deregulierung; eine Politik der sozialen</del><ins>Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer</ins> ',
                    6 => '<span class="lineNumber" data-line-number="7"></span>Spaltung unserer Gesellschaften; ein<del>e Politik, die</del><ins> Wirtschaftsmodell, das</ins> auch un<del>populär</del><ins>bequem</ins>e ',
                    7 => '<span class="lineNumber" data-line-number="8"></span>Strukturreformen <del>ang</del><ins>mit einbezi</ins>eht, wenn diese zu nachhaltigem Wachstum und mehr ',
                ];

                $out = AmendmentSectionFormatter::getDiffLinesWithNumbersDebug($orig, $new);

                $this->assertEquals($expect, $out);
            }
        );

        // @TODO:
        // - <li>s that are changed
        // - <li>s that are deleted

    }
}
