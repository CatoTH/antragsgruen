<?php

namespace unit;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DiffRenderer;
use app\models\sectionTypes\TextSimple;
use Codeception\Specify;

class AmendmentSectionFormatterTest extends TestBase
{
    use Specify;

    /**
     */
    public function testEmptyDeletedSpaceAtEnd()
    {
        $strPre  = '<p>###LINENUMBER###Wir sind froh und dankbar über alle, die in der Krise anpacken statt bloß zu lamentieren. ###LINENUMBER###Das vielleicht hervorstechendste Moment der letzten Wochen und Monate ist die schier ###LINENUMBER###unendliche Hilfsbereitschaft und der Wille zu einem solidarischen Engagement für Flüchtlinge ###LINENUMBER###– und zwar quer durch alle Gesellschaftsschichten, in Stadt und Land. Wer dagegen in dieser ###LINENUMBER###Situation zündelt und Stimmung gegen Flüchtlinge schürt, handelt unverantwortlich. Hier ###LINENUMBER###wissen wir die vielen Bürger*innen in diesem Land auf unserer Seite, die sich dem rechten ###LINENUMBER###Mob entgegenstellen, der die Not von Schutzsuchenden für Hass und rechtsextreme Propaganda ###LINENUMBER###missbraucht.</p>';
        $strPost = '<p>Wir sind froh und dankbar über alle, die in der Krise anpacken statt bloß zu lamentieren. Das vielleicht hervorstechendste Moment der letzten Wochen und Monate ist die schier unendliche Hilfsbereitschaft und der Wille zu einem solidarischen Engagement für Flüchtlinge – und zwar quer durch alle Gesellschaftsschichten, in Stadt und Land. Wer dagegen in dieser Situation zündelt und Stimmung gegen Flüchtlinge schürt, handelt unverantwortlich.</p>
<p>Hier wissen wir die vielen Bürger*innen in diesem Land auf unserer Seite, die sich konsequent rechtsextremen Tendenzen entgegenstellen, welche die Not von Schutzsuchenden für Hass und populistische Propaganda missbrauchen.</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_INLINE);

        $text   = TextSimple::formatDiffGroup($diffGroups);
        $expect = '<h4 class="lineSummary">Von Zeile 6 bis 10:</h4><div><p>Situation zündelt und Stimmung gegen Flüchtlinge schürt, handelt unverantwortlich.<br><ins class="space">[Zeilenumbruch]</ins><ins><br></ins>Hier wissen wir die vielen Bürger*innen in diesem Land auf unserer Seite, die sich <del>dem rechten </del><del>Mob</del><ins>konsequent rechtsextremen Tendenzen</ins> entgegenstellen, <del>der</del><ins>welche</ins> die Not von Schutzsuchenden für Hass und <del>rechtsextreme</del><ins>populistische</ins> Propaganda missbrauch<del>t</del><ins>en</ins>.</p></div>';
        $this->assertEquals($expect, $text);
    }

    public function testInlineFormatting()
    {
        $strPre  = '<p>Test 123</p>';
        $strPost = '<p>Test</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_INLINE);

        $this->assertEquals(1, count($diffGroups));

        $text   = TextSimple::formatDiffGroup($diffGroups);
        $expect = '<h4 class="lineSummary">In Zeile 1 löschen:</h4><div><p>Test<del style="color:#FF0000;text-decoration:line-through;"> 123</del></p></div>';
        $this->assertEquals($expect, $text);
    }


    /**
     */
    public function testLineBreaksWithinParagraphs()
    {
        // 'Line breaks within paragraphs'
        $orig = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf maßlose Deregulierung; eine Politik der sozialen Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; eine Politik, die auch unpopuläre Strukturreformen angeht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>Die Kaputtsparpolitik ist gescheitert<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        $new = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; ein Wirtschaftsmodell, das auch unbequeme Strukturreformen mit einbezieht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>Die Kaputtsparpolitik ist gescheitert<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        $expect = [
            [
                'text'     => '###LINENUMBER###Innovationen setzt statt auf <del>maßlose Deregulierung; eine Politik der sozialen</del><ins>Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer</ins> ',
                'lineFrom' => 5,
                'lineTo'   => 5,
                'newLine'  => false,
            ],
            [
                'text'     => '###LINENUMBER###Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen ',
                'lineFrom' => 6,
                'lineTo'   => 6,
                'newLine'  => false,
            ],
            [
                'text'     => 'Spaltung unserer Gesellschaften; <del>eine Politik, die</del><ins>ein Wirtschaftsmodell, das</ins> auch <del>unpopuläre</del><ins>unbequeme</ins> ',
                'lineFrom' => 7,
                'lineTo'   => 7,
                'newLine'  => false,
            ],
            [
                'text'     => 'Strukturreformen <del>angeht</del><ins>mit einbezieht</ins>, wenn diese zu nachhaltigem Wachstum und mehr ',
                'lineFrom' => 8,
                'lineTo'   => 8,
                'newLine'  => false,
            ],
        ];

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($orig);
        $formatter->setTextNew($new);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_CLASSES);


        $this->assertEquals($expect, $diffGroups);

        // @TODO:
        // - <li>s that are changed
        // - <li>s that are deleted
    }
}
