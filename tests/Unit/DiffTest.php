<?php

namespace Tests\Unit;

use app\components\diff\DataTypes\DiffWord;
use app\components\diff\Diff;
use app\components\diff\DiffRenderer;
use app\components\diff\Engine;
use app\components\HTMLTools;
use app\models\exceptions\Internal;
use app\models\SectionedParagraph;
use Codeception\Attribute\Incomplete;
use Tests\Support\Helper\TestBase;

class DiffTest extends TestBase
{
    public function testLineNumberBeforeActualChange(): void
    {
        $engine = new Engine();
        $engine->setIgnoreStr('###LINENUMBER###');

        $array1 = ['1', '2', '###LINENUMBER###', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '###LINENUMBER###', '13', '14'];
        $array2 = ['1', '2', '3', '4', '5', '6', '11', '12', '13', '14'];
        $returned = $engine->compareArrays($array1, $array2, false);

        $this->assertSame([
            ['1', 0], ['2', 0],
            ['###LINENUMBER###', 0], // This should not be marked as deleted
            ['3', 0], ['4', 0], ['5', 0], ['6', 0],
            ['7', 1], ['8', 1], ['9', 1], ['10', 1], // Deleted
            ['11', 0], ['12', 0],
            ['###LINENUMBER###', 0], // This should not be marked as deleted
            ['13', 0], ['14', 0],
        ], $returned);
    }

    public function testEdgeCaseUnchangedPrefixPostfix(): void
    {
        $lineOld = '<ul><li value="1">###LINENUMBER###Hier ein Anfang. Der Einzelhandel ###LINENUMBER###hat bereits weitere Marktanteile an den Onlinehandel verloren.</li></ul>';
        $lineNew = '<ul><li>Hier ein Anfang. Der Strukturwandel des Handels in Richtung online hat sich beschleunigt.</li></ul>';
        $combined = '<ul><li value="1">###LINENUMBER###Hier ein Anfang. Der ###DEL_START###Einzelhandel###DEL_END######INS_START###Strukturwandel des Handels in Richtung online hat sich beschleunigt###INS_END### ###LINENUMBER###hat bereits weitere Marktanteile an den Onlinehandel verloren###INS_END###.</li></ul>';

        $diff         = new Diff();
        $return = $diff->getUnchangedPrefixPostfix($lineOld, $lineNew, $combined);

        $this->assertSame([
            '<ul><li value="1">###LINENUMBER###Hier ein Anfang. ',
            'Der Einzelhandel ###LINENUMBER###hat bereits weitere Marktanteile an den Onlinehandel verloren.',
            'Der Strukturwandel des Handels in Richtung online hat sich beschleunigt.',
            'Der ###DEL_START###Einzelhandel###DEL_END######INS_START###Strukturwandel des Handels in Richtung online hat sich beschleunigt###INS_END### ###LINENUMBER###hat bereits weitere Marktanteile an den Onlinehandel verloren###INS_END###.',
            '</li></ul>',
        ], $return);
    }

    public function testShortLineWithManyChanges(): void
    {
        $orig     = '<p>Wir bieten einen Gegenpol zur Staatlichen Erziehung in dieser Gesellschaft.</p>';
        $new      = '<p>Der Bundesvorstand untersetzt, in Vorbereitung der Bundestagswahl, diese Forderungen mit konkreten Reformvorhaben.</p>';
        $expected = '###DEL_START###<p>Wir bieten einen Gegenpol zur Staatlichen Erziehung in dieser Gesellschaft.</p>###DEL_END######INS_START###<p>Der Bundesvorstand untersetzt, in Vorbereitung der Bundestagswahl, diese Forderungen mit konkreten Reformvorhaben.</p>###INS_END###';
        $diff     = new Diff();
        $out      = $diff->computeLineDiff($orig, $new);
        $this->assertSame($expected, $out);
    }

    public function testNoDiffInLink(): void
    {
        $orig     = '<p>[1] Der Vorschlag, ein Datenschutz-Grundrecht in das Grundgesetz einzufügen, fand bisher nicht die erforderliche Mehrheit. Personenbezogene Daten sind jedoch nach Art. 8 der EU-Grundrechtecharta geschützt. (<a href="https://de.wikipedia.org/wiki/Informationelle_Selbstbestimmung">https://de.wikipedia.org/wiki/Informationelle_Selbstbestimmung</a>)]</p>';
        $new      = '<p>[1] Der Vorschlag, ein Datenschutz-Grundrecht in das Grundgesetz einzufügen, fand bisher nicht die erforderliche Mehrheit. (<a href="https://de.wikipedia.org/wiki/Informationelle_Selbstbestimmung">https://de.wikipedia.org/wiki/Informationelle_Selbstbestimmung</a>)]</p>';
        $expected = '<p>[1] Der Vorschlag, ein Datenschutz-Grundrecht in das Grundgesetz einzufügen, fand bisher nicht die erforderliche Mehrheit.###DEL_START### Personenbezogene Daten sind jedoch nach Art. 8 der EU-Grundrechtecharta geschützt.###DEL_END### (<a href="https://de.wikipedia.org/wiki/Informationelle_Selbstbestimmung">https://de.wikipedia.org/wiki/Informationelle_Selbstbestimmung</a>)]</p>';
        $diff     = new Diff();
        $out      = $diff->computeLineDiff($orig, $new);
        $this->assertSame($expected, $out);
    }

    public function testOnlyGraphemeIsChanging(): void
    {
        $orig = '<p>🙂🙂🙂🙂🙂🙂🫶🏼🙂🙂🙂🙂🙂🙂</p>';
        $new = '<p>🙂🙂🙂🙂🙂🙂🫶🏾🙂🙂🙂🙂🙂🙂</p>';
        $expected = '<p>🙂🙂🙂🙂🙂🙂###DEL_START###🫶🏼###DEL_END######INS_START###🫶🏾###INS_END###🙂🙂🙂🙂🙂🙂</p>';
        $diff = new Diff();
        $out = $diff->computeLineDiff($orig, $new);
        $this->assertSame($expected, $out);
    }

    public function testNoGroupingBeyondLists(): void
    {
        $orig     = '<ul><li><ul><li><p>Der große Oxmox riet ihr davon ab, da es dort wimmele von bösen Kommata, wilden Fragezeichen und hinterhältigen Semikoli, doch das Blindtextchen ließ sich nicht beirren.</p></li></ul></li></ul>';
        $new      = '<ul><li><ul><li><p>Der große Oxmox riet ihr davon ab, doch das Blindtextchen ließ sich nicht beirren.</p></li><li><p>Noch eine neuer Punkt an dritter Stelle</p></li></ul></li></ul>';
        $expected = '<ul><li><ul><li><p>Der große Oxmox riet ihr davon ab, ###DEL_START###da es dort wimmele von bösen Kommata, wilden Fragezeichen und hinterhältigen Semikoli, ###DEL_END###doch das Blindtextchen ließ sich nicht beirren.###INS_START###</p></li><li><p>Noch eine neuer Punkt an dritter Stelle###INS_END###</p></li></ul></li></ul>';
        $diff     = new Diff();
        $out      = $diff->computeLineDiff($orig, $new);
        $this->assertSame($expected, $out);
    }

    public function testNoChangingParagraphTypes(): void
    {
        $orig     = '<p>###LINENUMBER###3) Eine Bekämpfung von Fluchtursachen und nicht der Geflüchteten</p>';
        $new      = '<ul><li>in Gesprächen mit (Vertreter*innen) der SPD, der Linkspartei und der Grünen</li></ul>';
        $expected = '###DEL_START###<p>###LINENUMBER###3) Eine Bekämpfung von Fluchtursachen und nicht der Geflüchteten</p>###DEL_END######INS_START###<ul><li>in Gesprächen mit (Vertreter*innen) der SPD, der Linkspartei und der Grünen</li></ul>###INS_END###';
        $diff     = new Diff();
        $out      = $diff->computeLineDiff($orig, $new);
        $this->assertSame($expected, $out);
    }

    #[Incomplete('Does not work yet')]
    public function testBreakListpintIntoTwo(): void
    {
        $orig   = [
            '<ul><li><p>Es packte seine sieben Versalien, schob sich sein Initial in den Gürtel und machte sich auf den Weg.</p><ul><li><p>Als es die ersten Hügel des Kursivgebirges erklommen hatte, warf es einen letzten Blick zurück auf die Skyline</p></li><li><p>seiner Heimatstadt Buchstabhausen, die Headline von Alphabetdorf und die Subline seiner eigenen Straße, der Zeilengasse.</p></li></ul></li></ul>'
        ];
        $new    = [
            '<ul><li><p>Es packte seine sieben Versalien, schob sich sein Initial in den Gürtel und machte sich auf den Weg.</p></li></ul>',
            '<ul><li><p>Als es die ersten Hügel des Kursivgebirges erklommen hatte, warf es einen letzten Blick zurück auf die Skyline</p><ul><li><p>Bla 2</p></li><li><p>seiner Heimatstadt Buchstabhausen, die Headline von Alphabetdorf und die Subline seiner eigenen Straße, der Zeilengasse.</p></li></ul></li></ul>'
        ];
        $expect = [
            // @TODO
        ];
        $diff   = new Diff();
        $arr    = $diff->compareHtmlParagraphs($orig, $new, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $arr);
    }

    private function assertDiffWordEquals($word, $diff, $amendmentId, $diffWord): void
    {
        $expected = new DiffWord();
        $expected->word = $word;
        $expected->diff = $diff;
        $expected->amendmentId = $amendmentId;
        $this->assertEqualsCanonicalizing($expected, $diffWord);
    }

    public function testInlineDiffToWordBased(): void
    {
        $orig = ['<ul><li>Test1</li></ul>', '<ul><li>Test3</li></ul>'];
        $new  = ['<ul><li>Test1</li></ul>', '<ul><li>Test2</li></ul>', '<ul><li>Test3</li></ul>'];
        $diff = new Diff();
        try {
            $arr = $diff->compareHtmlParagraphsToWordArray($orig, $new);
            $this->assertCount(2, $arr);
            $elements = count($arr[0]);

            $this->assertDiffWordEquals('</ul>', '</ul>###INS_START###<ul><li>Test2</li></ul>###INS_END###', null, $arr[0][$elements - 1]);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }

        $orig = ['<ul><li>Test1</li></ul>', '<ul><li>Test3</li></ul>', '<ul><li>Test3</li></ul>'];
        $new  = ['<p>Neue Zeile</p>'];
        $diff = new Diff();
        try {
            $arr = $diff->compareHtmlParagraphsToWordArray($orig, $new);
            // @TODO Insert at the beginning or end, not in the middle
            $all = json_encode($arr);
            $this->assertFalse(mb_strpos($all, '###EMPTYINSERTED###'));
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }

        $orig = ['<ul><li>Seltsame Zeichen: Test</li></ul>'];
        $new  = ['<ul><li>Seltsame Zeichen: Test</li></ul>'];
        $diff = new Diff();
        try {
            $diff->compareHtmlParagraphsToWordArray($orig, $new);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }

        $orig = ['<strong>Tes1 45666</strong> kjhkjh kljlkjlkj'];
        $new  = ['Tes1 45666 kjhkjh<br>kljlkjlkj'];
        $diff = new Diff();
        try {
            $words = $diff->compareHtmlParagraphsToWordArray($orig, $new);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }

        $this->assertDiffWordEquals('kjhkjh ', 'kjhkjh ###DEL_END######INS_START###45666 kjhkjh<br>###INS_END###', null, $words[0][5]);


        $orig = ['<ul><li>Wir sind Nummer 1</li></ul>'];
        $new  = ['<ul><li>Wir bla bla</li></ul>', '<ul><li>Wir sind Nummer 1</li></ul>'];
        $diff = new Diff();
        try {
            $words = $diff->compareHtmlParagraphsToWordArray($orig, $new);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }
        $this->assertCount(1, $words);
        $this->assertSame('###INS_START###<ul><li>Wir bla bla</li></ul>###INS_END###<ul>', $words[0][0]->diff);


        $orig = ['Test1 Test 2 der Test 3 Test4'];
        $new  = ['Test1 Test 2 die Test 3 Test4'];
        $diff = new Diff();
        try {
            $words = $diff->compareHtmlParagraphsToWordArray($orig, $new);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }
        $this->assertSame('###DEL_START###der###DEL_END######INS_START###die###INS_END### ', $words[0][3]->diff);


        $orig = ['Test1 test123456test Test4'];
        $new  = ['Test1 test12356test Test4'];
        $diff = new Diff();
        try {
            $words = $diff->compareHtmlParagraphsToWordArray($orig, $new);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }
        $this->assertDiffWordEquals('Test1 ', 'Test1 ', null, $words[0][0]);
        $this->assertDiffWordEquals('test123456test ', 'test123###DEL_START###4###DEL_END###56test ', null, $words[0][1]);
        $this->assertDiffWordEquals('Test4', 'Test4', null, $words[0][2]);


        $orig = [
            '<p>Normaler Text wieder<sup>Hochgestellt</sup>.<br>
Neue Zeile<sub>Tiefgestellt</sub>.</p>'
        ];
        $new  = ['<p>Normaler Text wieder.</p>'];
        $diff = new Diff();
        try {
            $words = $diff->compareHtmlParagraphsToWordArray($orig, $new, 1);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }
        $this->assertCount(16, $words[0]);


        $orig = [
            '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch. Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>'
        ];
        $new  = [
            '<p>Bavaria ipsum dolor sit amet Biazelt Auffisteign Schorsch.</p>',
            '<p>Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>',
        ];
        $diff = new Diff();
        try {
            $arr = $diff->compareHtmlParagraphsToWordArray($orig, $new, 1);
            $this->assertCount(1, $arr);
            $this->assertDiffWordEquals('.', '.###DEL_END###', 1, $arr[0][21]);
            $this->assertDiffWordEquals('</p>', '</p>###INS_START###<p>Griasd eich midnand etza nix Gwiass woass ma ned owe.</p>###INS_END###', 1, $arr[0][22]);
        } catch (Internal $e) {
            echo $e->getMessage();
            echo "\n";
            die();
        }
    }


    public function testGroupOperations(): void
    {
        $src     = [
            [
                '<p>###LINENUMBER###Wui helfgod Wiesn. Sauwedda an Brezn, abfieseln.</p>',
                Engine::DELETED
            ],
            [
                '<p>###LINENUMBER###Wui helfgod Wiesn.</p>',
                Engine::INSERTED
            ],
            [
                '',
                Engine::UNMODIFIED
            ]
        ];
        $diff    = new Diff();
        $grouped = $diff->groupOperations($src, '');
        $this->assertSame($src, $grouped); // Should not be changed


        $operations = [
            ['test 123', Engine::UNMODIFIED],
            ['<pre>###LINENUMBER###PRE', Engine::DELETED],
            ['* Test', Engine::DELETED],
            ['</pre>', Engine::DELETED],
            ['test 123', Engine::UNMODIFIED],
        ];
        $expected   = [
            ['test 123', Engine::UNMODIFIED],
            ['<pre>###LINENUMBER###PRE' . "\n" . '* Test' . "\n" . '</pre>', Engine::DELETED],
            ['test 123', Engine::UNMODIFIED],
        ];
        $diff       = new Diff();
        $out        = $diff->groupOperations($operations, "\n");
        $this->assertSame($expected, $out);
    }


    public function testWordDiff(): void
    {
        $diff     = new Diff();
        $renderer = new DiffRenderer();

        $orig = 'Zeichen das sie überwunden';
        $new  = 'Zeichen, dass sie überwunden';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame('Zeichen<del> das</del><ins>, dass</ins> sie überwunden', $out);

        $orig = 'Hass';
        $new  = 'Hass, dem Schüren von Ressentiments';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame('Hass<ins>, dem Schüren von Ressentiments</ins>', $out);

        $orig = 'Bürger*innen ';
        $new  = 'Menschen ';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame('<del>Bürger*innen</del><ins>Menschen</ins> ', $out);

        $orig = 'dekonstruieren.';
        $new  = 'dekonstruieren. Andererseits sind gerade junge Menschen';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame('dekonstruieren.<ins> Andererseits sind gerade junge Menschen</ins>', $out);

        $orig = 'dekonstruieren. Andererseits sind gerade junge Menschen';
        $new  = 'dekonstruieren.';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame('dekonstruieren.<del> Andererseits sind gerade junge Menschen</del>', $out);

        $orig = 'So viele Menschen wie';
        $new  = 'Sie steht vor dieser Anstrengung gemeinsam usw. So viele Menschen wie';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame('<ins>Sie steht vor dieser Anstrengung gemeinsam usw. </ins>So viele Menschen wie', $out);

        $orig = 'Test1 Test 2 der Test 3 Test4';
        $new  = 'Test1 Test 2 die Test 3 Test4';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame('Test1 Test 2 <del>der</del><ins>die</ins> Test 3 Test4', $out);

        $orig = '###LINENUMBER###Bildungsbereich. Der Bund muss sie unterstützen. Hier darf das Kooperationsverbot nicht im ###LINENUMBER###Wege stehen.';
        $new  = 'Bildungsbereich.';
        $out  = $renderer->renderHtmlWithPlaceholders($diff->computeWordDiff($orig, $new));
        $this->assertSame(
            '###LINENUMBER###Bildungsbereich.<del> Der Bund muss sie unterstützen. Hier darf das Kooperationsverbot nicht im ###LINENUMBER###Wege stehen.</del>',
            $out
        );
    }

    public function testLinenumberForcelinebreak(): void
    {
        // Unrealistic test case - $new would be two paragraphs
        $orig = new SectionedParagraph('<p>###LINENUMBER###Wir wollen eine Wirtschaftsweise, in der alle Rohstoffe immer wieder neu verarbeitet und ###LINENUMBER###nicht auf einer Deponie landen oder verbrannt werden. Auch die Verschiffung unseres ###LINENUMBER###Elektroschrotts in Entwicklungs- und Schwellenländer ist keine Lösung. Sie verursacht dort ###LINENUMBER###schwere Umweltschäden. Wir wollen deshalb ein Wertstoffgesetz, durch das Herstellern von ###LINENUMBER###Produkten und Verpackungen eine Produktverantwortung zukommt, indem ambitionierte, aber ###LINENUMBER###machbare Recyclingziele eingeführt werden. Dadurch werden Rohstoffpreise befördert, die die ###LINENUMBER###sozialen und ökologischen Folgekosten der Rohstoffgewinnung und ihrer Verwertung am Ende des ###LINENUMBER###Produktlebenszyklus und gegenüber den Verbraucher*innen ehrlich abbilden. So wird der ###LINENUMBER###Einsatz von Recyclingmaterial gegenüber Primärmaterial wettbewerbsfähig. Wir setzen uns ###LINENUMBER###dafür ein, dass für gewerbliche Abfälle und Bauabfälle die gleichen ökologischen ###LINENUMBER###Anforderungen gelten wie für die Hausmüllsammlung und -verwertung.</p>', 0, 0);
        $new  = new SectionedParagraph('<p>Der beste Abfall ist der, der nicht entsteht. Wir wollen eine Wirtschaftsweise, in der Material- und Rohstoffeffizienz an erster Stelle stehen und in der alle Rohstoffe immer wieder neu verarbeitet werden und nicht auf einer Deponie landen, in Entwicklungs- und Schwellenländer exportiert oder verbrannt werden. Wir setzen uns für echte Kreislaufwirtschaft mit dem perspektivischen Ziel von „Zero Waste“ ein und wollen den Rohstoffschatz, der im vermeintlichen Müll schlummert heben. Wir wollen deshalb ein Wertstoffgesetz, durch das Herstellern von<br>Produkten und Verpackungen eine ökologische Produktverantwortung zukommt, indem ambitionierte, abermachbare Recyclingziele sowie Ziele zur Material- und Rohstoffeffizienz eingeführt werden. Wir wollen einen „Recycling-Dialog“ mit Industrie, Verbraucher- und Umweltverbänden sowie der Abfallwirtschaft ins Leben rufen, um gemeinsam ambitioniertere Standards in Bezug auf weniger Rohstoffeinsatz und mehr Recycling zu entwickeln und Anreize für die Verwendung von Recyclingmaterialien zu schaffen.</p><p>Wir setzen uns dafür ein, dass die Rohstoffpreise die<br>sozialen und ökologischen Folgekosten der Rohstoffgewinnung und ihrer Verwertung am Ende des<br>Produktlebenszyklus und gegenüber den Verbraucher*innen ehrlich abbilden. So wird Ökologie zum Wettbewerbsvorteil: Wer weniger Rohstoffe verbraucht oder Recyclingmaterial anstatt Primärmaterial, spart Geld, Damit der gesamte (Sekundär)-Rohstoffschatz gehoben werden kann, setzen wir uns außerdem dafür ein , dass für gewerbliche Abfälle und Bauabfälle die gleichen ökologischen<br>Anforderungen gelten wie für die Hausmüllsammlung und -verwertung.</p>', 0, 0);

        $diff = new Diff();
        $out  = $diff->compareHtmlParagraphs([$orig], [$new], DiffRenderer::FORMATTING_CLASSES);

        $expect = ['<p><ins>Der beste Abfall ist der, der nicht entsteht. </ins>###LINENUMBER###Wir wollen eine Wirtschaftsweise, <ins>in der Material- und Rohstoffeffizienz an erster Stelle stehen und </ins>in der alle Rohstoffe immer wieder neu verarbeitet <ins>werden </ins>und ###LINENUMBER###nicht auf einer Deponie landen<del> oder verbrannt werden. Auch die Verschiffung unseres ###LINENUMBER###Elektroschrotts</del><ins>,</ins> in Entwicklungs- und Schwellenländer <del>ist keine Lösung</del><ins>exportiert oder verbrannt werden</ins>. <del>Sie verursacht dort ###LINENUMBER###schwere Umweltschäden</del><ins>Wir setzen uns für echte Kreislaufwirtschaft mit dem perspektivischen Ziel von „Zero Waste“ ein und wollen den Rohstoffschatz, der im vermeintlichen Müll schlummert heben</ins>. Wir wollen deshalb ein Wertstoffgesetz, durch das Herstellern von<del> </del><ins><br></ins>###LINENUMBER###Produkten und Verpackungen eine <ins>ökologische </ins>Produktverantwortung zukommt, indem ambitionierte, <del>aber ###LINENUMBER###machbare</del><ins>abermachbare</ins> Recyclingziele <ins>sowie Ziele zur Material- und Rohstoffeffizienz </ins>eingeführt werden. <del>Dadurch werden Rohstoffpreise befördert,</del><ins>Wir wollen einen „Recycling-Dialog“ mit Industrie, Verbraucher- und Umweltverbänden sowie der Abfallwirtschaft ins Leben rufen, um gemeinsam ambitioniertere Standards in Bezug auf weniger Rohstoffeinsatz und mehr Recycling zu entwickeln und Anreize für</ins> die <ins>Verwendung von Recyclingmaterialien zu schaffen.</ins></p><p><ins>Wir setzen uns dafür ein, dass </ins>die <ins>Rohstoffpreise die<br></ins>###LINENUMBER###sozialen und ökologischen Folgekosten der Rohstoffgewinnung und ihrer Verwertung am Ende des<del> </del><ins><br></ins>###LINENUMBER###Produktlebenszyklus und gegenüber den Verbraucher*innen ehrlich abbilden. So wird <del>der ###LINENUMBER###Einsatz von Recyclingmaterial gegenüber Primärmaterial wettbewerbsfähig.</del><ins>Ökologie zum Wettbewerbsvorteil:</ins> <del>Wir</del><ins>Wer weniger Rohstoffe verbraucht oder Recyclingmaterial anstatt Primärmaterial, spart Geld, Damit der gesamte (Sekundär)-Rohstoffschatz gehoben werden kann,</ins> setzen <ins>wir </ins>uns <ins>außerdem </ins>###LINENUMBER###dafür ein<ins> </ins>, dass für gewerbliche Abfälle und Bauabfälle die gleichen ökologischen<del> </del><ins><br></ins>###LINENUMBER###Anforderungen gelten wie für die Hausmüllsammlung und -verwertung.</p>'];
        $this->assertSame($expect, $out);
    }

    #[Incomplete('TODO')]
    public function testDeleteMultipleParagraphs(): void
    {
        $orig = '<p>Noch Frieden</p>
<p>Etwas Text in P</p>
<ul>
    <li>Eine erste Zeile</li>
    <li>Zeile 2 mit etwas mehr</li>
    <li>Ganz was anderes</li>
    <li>Noch eine Zeile mit etwas Text</li>
    <li>Voll <strong>fett</strong> der Text</li>
</ul>
<p>Und noch etwas zum Abschluss</p>';
        $new  = '<p>Noch Frieden</p>
<ul>
    <li>Eine <em>erste</em> Zeile</li>
    <li>Zeile 2 mit sdff etwas mehr</li>
    <li>Noch sdfsd eine Zeile mit etwas Text</li>
    <li>Voll <strong>fett</strong> der Text</li>
</ul>
<p>Und noch etwas zum Abschluss</p>';

        $expectedDiff   = [
            '<p>Noch Frieden</p>',
            '<p class="deleted">Etwas Text in P</p>',
            '<ul><li>Eine <del>erste </del><ins><em>erste</em> </ins>Zeile</li></ul>',
            '<ul><li>Zeile 2 mit <ins>sdff </ins>etwas mehr</li></ul>',
            '<ul class="deleted"><li>Ganz was anderes</li></ul>',
            '<ul><li>Noch <ins>sdfsd </ins>eine Zeile mit etwas Text</li></ul>',
            '<ul><li>Voll <strong>fett</strong> der Text</li></ul>',
            '<p>Und noch etwas zum Abschluss</p>',
        ];
        $origParagraphs = HTMLTools::sectionSimpleHTML($orig);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($new);

        $diff = new Diff();
        $out  = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expectedDiff, $out);
    }

    public function testInsertWithSameBeginningWord(): void
    {
        $orig     = [
            new SectionedParagraph('<ul><li>Wir sind Nummer 1</li></ul>', 0, 0)
        ];
        $new      = [
            new SectionedParagraph('<ul><li>Wir bla bla</li></ul>', 0, 0),
            new SectionedParagraph('<ul><li>Wir sind Nummer 1</li></ul>', 1, 1)
        ];
        $expected = ['<ul class="inserted"><li>Wir bla bla</li></ul><ul><li>Wir sind Nummer 1</li></ul>'];
        $diff     = new Diff();
        $out      = $diff->compareHtmlParagraphs($orig, $new, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expected, $out);
    }

    #[Incomplete('TODO')]
    public function testSwitchAndInsertListItems(): void
    {
        $orig = '<p>Die Stärkung einer europäischen Identität – ohne die Verwischung historischer Verantwortung und politischer Kontinuitäten – ist für eine zukünftige Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen wird und in der Erinnerungsarbeit aufgegriffen werden muss.</p>
<p>Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>
<ul>
<li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li>
	<li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust und die nationalsozialistischen Verbrechen, die von Deutschland ausgingen, wach zu halten und gemeinsam Sorge dafür zu tragen, „dass Auschwitz nie wieder sei!“.</li>
	<li>Wir sehen die Notwendigkeit eines stetigen Austarierens und Diskurses, um sich angemessen mit anderen historischen Ereignissen auseinanderzusetzen, die aufgrund der Herkunftsgeschichte vieler Mitglieder relevant werden, ohne dabei den Holocaust in irgendeiner Weise zu relativieren.</li>
</ul>';
        $new  = '<p> </p>
<p>Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>
<ul>
<li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust und die nationalsozialistischen Verbrechen, die von Deutschland ausgingen, wach zu halten und gemeinsam Sorge dafür zu tragen, „dass Auschwitz nie wieder sei!“.</li>
	<li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li>
	<li> </li>
	<li>Wir sehen die Notwendigkeit eines stetigen Austarierens und Diskurses, um sich angemessen mit anderen historischen Ereignissen auseinanderzusetzen, die aufgrund der Herkunftsgeschichte vieler Mitglieder relevant werden, ohne dabei den Holocaust in irgendeiner Weise zu relativieren.</li>
</ul>';

        $origParagraphs = HTMLTools::sectionSimpleHTML($orig);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($new);

        $diff = new Diff();
        $out  = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $this->assertSame(
            '<p class="deleted">Die Stärkung einer europäischen Identität – ohne die Verwischung historischer Verantwortung und politischer Kontinuitäten – ist für eine zukünftige Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen wird und in der Erinnerungsarbeit aufgegriffen werden muss.</p>',
            $out[0]
        );
        $this->assertSame(
            '<p><del>Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. </del>Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>',
            $out[1]
        );
        $this->assertSame(
            '<ul class="deleted"><li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li></ul>',
            $out[2]
        );
        $this->assertSame(
            '<ul><li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust und die nationalsozialistischen Verbrechen, die von Deutschland ausgingen, wach zu halten und gemeinsam Sorge dafür zu tragen, „dass Auschwitz nie wieder sei!“.</li></ul><ul class="inserted"><li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li></ul>',
            $out[3]
        );
        $this->assertSame(
            '<ul><li>Wir sehen die Notwendigkeit eines stetigen Austarierens und Diskurses, um sich angemessen mit anderen historischen Ereignissen auseinanderzusetzen, die aufgrund der Herkunftsgeschichte vieler Mitglieder relevant werden, ohne dabei den Holocaust in irgendeiner Weise zu relativieren.</li></ul>',
            $out[4]
        );
    }


    public function testReplaceListByP(): void
    {
        $orig           = '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li>' .
                          '<li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li>' .
                          '<li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li>' .
                          '<li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';
        $new            = '<p>Test 456</p>';
        $origParagraphs = HTMLTools::sectionSimpleHTML($orig);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($new);

        $diff      = new Diff();
        $diffParas = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $expected = [
            '<ul class="deleted"><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul>',
            '<ul class="deleted"><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li></ul>',
            '<ul class="deleted"><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li></ul><p class="inserted">Test 456</p>',
            '<ul class="deleted"><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>',
        ];
        $this->assertSame($expected, $diffParas);
    }


    public function testReplaceParagraph(): void
    {
        $diff = new Diff();

        $str1           = '<p>Unchanging line</p>
<p>Das wollen wir mit unserer Zeitpolitik ermöglichen. Doch wie die Aufgaben innerhalb der Familie verteilt werden, ' .
                          'entscheidet sich heute oft in ernüchternder Weise: Selbst wenn Paare gleichberechtigt und in ' .
                          'gegenseitigem Einvernehmen die Rollenverteilung miteinander ausmachen wollen, scheitern sie zu oft ' .
                          'an der Realität – und leben plötzlich Rollenbilder, die sie eigentlich so nie wollten. ' .
                          'Verkrustete Strukturen und Fehlanreize regieren in ihr Leben hinein; sie verhindern, dass Frauen und ' .
                          'Männer selbstbestimmt und auf Augenhöhe ihre Entscheidungen treffen können.</p>';
        $str2           = '<p>Unchanging line</p>
<p>Diesen Wunsch der Paare in die Realität umzusetzen ist das Ziel unserer Zeitpolitik. Hierfür sind verkrustete ' .
                          'patriarchalische Strukturen und Fehlanreize abzubauen, jedoch ohne dass neuer sozialer Druck auf ' .
                          'Familien entsteht. Damit Paare selbstbestimmt und auf Augenhöhe die Rollenverteilung in ihrer Familie ' .
                          'festlegen können, muss die Gesellschaft die Entscheidungen der Familien unabhängig von ihrem Ergebnis ' .
                          'akzeptieren und darf keine Lebensmodelle stigmatisieren.</p>';
        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);

        $expect = [
            '<p>Unchanging line</p>',
            '<p class="deleted">Das wollen wir mit unserer Zeitpolitik ermöglichen. Doch wie die Aufgaben innerhalb der Familie verteilt werden, entscheidet sich heute oft in ernüchternder Weise: Selbst wenn Paare gleichberechtigt und in gegenseitigem Einvernehmen die Rollenverteilung miteinander ausmachen wollen, scheitern sie zu oft an der Realität – und leben plötzlich Rollenbilder, die sie eigentlich so nie wollten. Verkrustete Strukturen und Fehlanreize regieren in ihr Leben hinein; sie verhindern, dass Frauen und Männer selbstbestimmt und auf Augenhöhe ihre Entscheidungen treffen können.</p>' .
            '<p class="inserted">Diesen Wunsch der Paare in die Realität umzusetzen ist das Ziel unserer Zeitpolitik. Hierfür sind verkrustete patriarchalische Strukturen und Fehlanreize abzubauen, jedoch ohne dass neuer sozialer Druck auf Familien entsteht. Damit Paare selbstbestimmt und auf Augenhöhe die Rollenverteilung in ihrer Familie festlegen können, muss die Gesellschaft die Entscheidungen der Familien unabhängig von ihrem Ergebnis akzeptieren und darf keine Lebensmodelle stigmatisieren.</p>'
        ];

        $diffParas = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $this->assertSame($expect, $diffParas);


        $str1           = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird Und noch etwas am Ende. Und noch etwas am Ende</p>';
        $str2           = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen Und noch etwas am Ende. Und noch etwas am Ende</p>';
        $expect         = [
            '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. <del>Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird Und noch etwas am Ende.</del>' .
            '<ins>Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen Und noch etwas am Ende.</ins> Und noch etwas am Ende</p>'
        ];
        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);

        $diffParas = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);
    }


    public function testDeletedSentenceAtEnd(): void
    {
        $origParagraphs = [
            new SectionedParagraph('<p>gesellschaftlich dominante Narrative zu hinterfragen und ggf. zu dekonstruieren. Andererseits sind gerade junge Menschen auf für sie geeignete Möglichkeiten und Wege des Gedenkens angewiesen, da sie selbst noch weniger über persönliche Erinnerungen verfügen und dennoch bereits den legitimen Anspruch auf Mitbestimmung haben. Wer Gesellschaft mitgestalten will, muss (also) erinnern können.</p>', 0, 0)
        ];
        $newParagraphs  = [
            new SectionedParagraph('<p>gesellschaftlich dominante Narrative zu hinterfragen und ggf. zu dekonstruieren.</p>', 0, 0)
        ];
        $expect         = ['<p>gesellschaftlich dominante Narrative zu hinterfragen und ggf. zu dekonstruieren.<del> Andererseits sind gerade junge Menschen auf für sie geeignete Möglichkeiten und Wege des Gedenkens angewiesen, da sie selbst noch weniger über persönliche Erinnerungen verfügen und dennoch bereits den legitimen Anspruch auf Mitbestimmung haben. Wer Gesellschaft mitgestalten will, muss (also) erinnern können.</del></p>'];

        $diff      = new Diff();
        $diffParas = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);
    }

    public function testParagraphs(): void
    {
        $diff = new Diff();

        $str1 = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. ' .
                'Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>' .
                '<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. ' .
                'A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';
        $str2 = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. ' .
                'Biaschlegl soi oans, zwoa, gsuffa Oachsdfsdfsdf helfgod im Beidl des basd scho i hob di liab. ' .
                'A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';

        /*
        $expect = ['<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. <del>Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</del></p>',
            '<p><del>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds</del>' .
            '<ins>Biaschlegl soi oans, zwoa, gsuffa Oachsdfsdfsdf</ins> helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>'];
        */
        /*
        $expect = [
            '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. ' .
            'Biaschlegl soi oans, zwoa, gsuffa <del>Oachkatzlschwoaf hod Wiesn</del><ins>Oachsdfsdfsdf helfgod im Beidl des basd scho i hob di liab. ' .
            'A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn</ins>.</p>',

            '<p class="deleted">Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. ' .
            'A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>',
        ];
        */
        $expect = [
            '<p class="deleted">I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>',
            '<p><del>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds</del><ins>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachsdfsdfsdf</ins> helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>',
        ];

        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $this->assertSame($expect, $diffParas);


        $str1   = '###LINENUMBER###Str1 Str2 Str3###LINENUMBER### Str4 Str5';
        $str2   = 'Str1 Str2 Str3 Str4';
        $expect = ['###LINENUMBER###Str1 Str2 Str3###LINENUMBER### Str4<del> Str5</del>'];

        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $this->assertSame($expect, $diffParas);


        $str1   = 'Abcdef abcdef Abcdef AAbcdef Abcdef';
        $str2   = 'Abcdef abcdefghi Abcdef AAbcdef preAbcdef';
        $expect = ['Abcdef abcdef<ins>ghi</ins> Abcdef AAbcdef <ins>pre</ins>Abcdef'];

        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);


        $str1   = 'ym Bla gagen lerd mal';
        $str2   = 'ym Blagagen lerd mal';
        $expect = ['ym Bla<del> </del>gagen lerd mal'];

        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);


        $str1   = 'uns dann als Zeichen das sie uns überwunden hatten';
        $str2   = 'uns dann als Zeichen, dass sie uns überwunden hatten';
        $expect = ['uns dann als Zeichen<del> das</del><ins>, dass</ins> sie uns überwunden hatten'];

        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);


        $str1   = 'Test <strong>Test1</strong> Test2';
        $str2   = 'Test <strong>Test2</strong> Test2';
        $expect = ['Test <strong>Test<del>1</del><ins>2</ins></strong> Test2'];

        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);
    }

    public function testTwoInserts(): void
    {
        $str1   = '<ul><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<p>###LINENUMBER###I waar soweid Blosmusi es nomoi.</p>';
        $str2   = '<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<ul><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>
<ul><li>Blabla</li></ul>
<p>I waar soweid Blosmusi es nomoi.</p>';
        $expect = [
            '<ul><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>' .
            '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>' .
            '<ul class="inserted"><li>Blabla</li></ul>',
            '<p>###LINENUMBER###I waar soweid Blosmusi es nomoi.</p>'
        ];

        $diff           = new Diff();
        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);
    }

    public function testTwoChangedLis(): void
    {
        $str1   = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li></ul>
<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';
        $str2   = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.asdasd</li></ul>
<ul><li>aWoibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';
        $expect = [
            '<ul><li>Test123</li></ul>',
            '<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.<ins>asdasd</ins></li></ul>',
            '<ul><li><ins>a</ins>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>'
        ];

        $diff           = new Diff();
        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);
    }

    public function testShiftMisplacedTags(): void
    {
        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['Newly ', Engine::INSERTED],
            ['inserted ', Engine::INSERTED],
            ['normal ', Engine::UNMODIFIED],
            [' text', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertSame($orig, $corrected);


        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['Old ', Engine::DELETED],
            ['deleted ', Engine::DELETED],
            ['normal ', Engine::UNMODIFIED],
            [' text', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertSame($orig, $corrected);


        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['New ', Engine::INSERTED],
            ['content', Engine::INSERTED],
            ['</p>', Engine::INSERTED],
            ['', Engine::INSERTED],
            ['<p>', Engine::INSERTED],
            ['more', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertSame([
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::INSERTED],
            ['New ', Engine::INSERTED],
            ['content', Engine::INSERTED],
            ['</p>', Engine::INSERTED],
            ['', Engine::INSERTED],
            ['<p>', Engine::UNMODIFIED],
            ['more', Engine::UNMODIFIED],
        ], $corrected);


        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['Deleted ', Engine::DELETED],
            ['content', Engine::DELETED],
            ['</p>', Engine::DELETED],
            ['', Engine::DELETED],
            ['<p>', Engine::DELETED],
            ['more', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertSame([
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::DELETED],
            ['Deleted ', Engine::DELETED],
            ['content', Engine::DELETED],
            ['</p>', Engine::DELETED],
            ['', Engine::DELETED],
            ['<p>', Engine::UNMODIFIED],
            ['more', Engine::UNMODIFIED],
        ], $corrected);
    }

    public function testLineDiffWithTags(): void
    {
        $strPre    = [new SectionedParagraph('<ul><li>Listenpunkt</li></ul>', 0, 0)];
        $strPost   = [new SectionedParagraph('<p>Test</p>', 0, 0)];
        $diff      = new Diff();
        $diffParas = $diff->compareHtmlParagraphs($strPre, $strPost, DiffRenderer::FORMATTING_CLASSES);
        $expected  = ['<ul class="deleted"><li>Listenpunkt</li></ul><p class="inserted">Test</p>'];
        $this->assertSame($expected, $diffParas);
    }


    public function testParagraphManyChanges(): void
    {
        $strPre  = '<p>###LINENUMBER###Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten ###LINENUMBER###sozialen Absicherung. Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen ###LINENUMBER###Renten-, Kranken- und Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus ist ###LINENUMBER###es in der Anfangsphase der Selbständigkeit und insbesondere bei Start-ups oft schwierig, die ###LINENUMBER###vollen Beitragslasten zu tragen. Wir wollen an Lösungen arbeiten, die angelehnt an den ###LINENUMBER###Gedanken der Künstlersozialkasse, für eine temporäre Unterstützung an dieser Stelle sorgen. ###LINENUMBER###Damit sich Gründer*innen leichter am Markt etablieren können, wollen wir den bürokratischen ###LINENUMBER###Aufwand senken. Eine einzige Anlaufstelle (One-Stop-Shop) würde ihre Situation deutlich ###LINENUMBER###verbessern. Hier sollen sämtliche Beratungsleistungen und bürokratische Anforderungen ###LINENUMBER###abwickelt werden, damit sie nicht im Behördendschungel aufgehalten werden.</p>';
        $strPost = '<p>Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. <em>Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus wollen wir eine Bürger*innenversicherung in Gesundheit und Pflege einführen. Auch die Rentenversicherung wollen wir schrittweise zu einer Bürger*innenversicherung weiterentwickeln. In einem ersten Schritt wollen wir die bisher nicht pflichtversicherten Selbständigen in die gesetzliche Rentenversicherung einbeziehen. Die Grüne Garantierente soll ein Signal speziell an Selbständige mit geringem Einkommen senden, dass sich die Beiträge zur Rentenversicherung auch lohnen. </em> Damit sich Gründer*innen leichter am Markt etablieren können, wollen wir den bürokratischen Aufwand senken. Eine einzige Anlaufstelle (One-Stop-Shop) würde ihre Situation deutlich verbessern. Hier sollen sämtliche Beratungsleistungen und bürokratische Anforderungen abwickelt werden, damit sie nicht im Behördendschungel aufgehalten werden.</p>';

        $origParagraphs = HTMLTools::sectionSimpleHTML($strPre);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($strPost);
        $diff           = new Diff();
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $expected = ['<p>###LINENUMBER###Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten ###LINENUMBER###sozialen Absicherung. <del>Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen ###LINENUMBER###Renten-, Kranken- und Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus ist ###LINENUMBER###es in der Anfangsphase der Selbständigkeit und insbesondere bei Start-ups oft schwierig, die ###LINENUMBER###vollen Beitragslasten zu tragen. Wir wollen an Lösungen arbeiten, die angelehnt an den ###LINENUMBER###Gedanken der Künstlersozialkasse, für eine temporäre Unterstützung an dieser Stelle sorgen.</del><ins><em>Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus wollen wir eine Bürger*innenversicherung in Gesundheit und Pflege einführen. Auch die Rentenversicherung wollen wir schrittweise zu einer Bürger*innenversicherung weiterentwickeln. In einem ersten Schritt wollen wir die bisher nicht pflichtversicherten Selbständigen in die gesetzliche Rentenversicherung einbeziehen. Die Grüne Garantierente soll ein Signal speziell an Selbständige mit geringem Einkommen senden, dass sich die Beiträge zur Rentenversicherung auch lohnen. </em></ins> ###LINENUMBER###Damit sich Gründer*innen leichter am Markt etablieren können, wollen wir den bürokratischen ###LINENUMBER###Aufwand senken. Eine einzige Anlaufstelle (One-Stop-Shop) würde ihre Situation deutlich ###LINENUMBER###verbessern. Hier sollen sämtliche Beratungsleistungen und bürokratische Anforderungen ###LINENUMBER###abwickelt werden, damit sie nicht im Behördendschungel aufgehalten werden.</p>'];
        $this->assertSame($expected, $diffParas);
    }

    public function testShortParagraph(): void
    {
        $strPre  = '<p><strong>Balance von Freiheit und Sicherheit für Solo-Selbstständige und Existenzgründer*innen</strong></p>';
        $strPost = '<p><strong>Balance von Freiheit und Sicherheit für Selbstständige und Existenzgründer*innen</strong></p>';

        $origParagraphs = HTMLTools::sectionSimpleHTML($strPre);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($strPost);
        $diff           = new Diff();
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $expected = ['<p><strong>Balance von Freiheit und Sicherheit für <del>Solo-</del>Selbstständige und Existenzgründer*innen</strong></p>'];
        $this->assertSame($expected, $diffParas);
    }

    public function testDeleteBeyondList(): void
    {
        $strPre  = "<p>###LINENUMBER###Test.</p>
<p>###LINENUMBER###<strong>To be deletedgi: </strong></p>
<ul><li>###LINENUMBER###Test 2</li></ul>
<ul><li>###LINENUMBER###Test 1</li></ul>
<p>###LINENUMBER###Also to be deleted.</p>";
        $strPost = "<p>Test.</p>";

        $origParagraphs = HTMLTools::sectionSimpleHTML($strPre);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($strPost);
        $diff           = new Diff();

        // var_dump($origParagraphs);
        // var_dump($newParagraphs);

        $diffParas = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $expected = [
            '<p>###LINENUMBER###Test.</p>',
            '<p class="deleted">###LINENUMBER###<strong>To be deletedgi: </strong></p>',
            '<ul class="deleted"><li>###LINENUMBER###Test 2</li></ul>',
            '<ul class="deleted"><li>###LINENUMBER###Test 1</li></ul>',
            '<p class="deleted">###LINENUMBER###Also to be deleted.</p>'
        ];
        $this->assertSame($expected, $diffParas);
    }

    public function testLiPSomething(): void
    {
        // From https://bdk.antragsgruen.de/39/motion/133/amendment/323
        $strPre  = '<ul><li>###LINENUMBER###Die Mobilisierung der Mittel für den internationalen Klimaschutz ist eine ###LINENUMBER###öffentliche Aufgabe.</li></ul>';
        $strPost = '<ul><li><p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>' . "\n" . '.</li></ul>';
        $expect  = [
            '<ul><li><p>###LINENUMBER###Die Mobilisierung der Mittel für den internationalen Klimaschutz ist <del>eine</del><ins>zum allergroßten Teil</ins> ###LINENUMBER###öffentliche Aufgabe<ins>, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</ins></p><ins>
</ins>.</li></ul>'
        ];

        $origParagraphs = HTMLTools::sectionSimpleHTML($strPre);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($strPost);
        $diff           = new Diff();
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $this->assertSame($expect, $diffParas);
    }


    public function testNoMessingUpLineNumbers(): void
    {
        $strPre   = '<p>###LINENUMBER###<strong>Anspruch und Ausblick</strong></p>
<p>###LINENUMBER###Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch ###LINENUMBER###weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft ###LINENUMBER###vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und ###LINENUMBER###biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkte ###LINENUMBER###und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die ###LINENUMBER###Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang ###LINENUMBER###hier bleiben werden, diesen Prozess verstärken.</p>
<p>###LINENUMBER###Die Stärkung einer europäischen Identität – ohne die Verwischung historischer ###LINENUMBER###Verantwortung und politischer Kontinuitäten – ist für eine zukünftige ###LINENUMBER###Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen ###LINENUMBER###wird und in der Erinnerungsarbeit aufgegriffen werden muss.</p>
<p>###LINENUMBER###Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. ###LINENUMBER###Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden ###LINENUMBER###Herausforderungen an:</p>';
        $strPost  = '<p><strong>Anspruch und Ausblick</strong></p>
<p>Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkten und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang hier bleiben werden, diesen Prozess verstärken.</p>
<p>Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>';
        $expected = [
            '<p>###LINENUMBER###<strong>Anspruch und Ausblick</strong></p>',
            '<p>###LINENUMBER###Die Zusammensetzung der in Deutschland lebenden Bevölkerung ändert sich auch ###LINENUMBER###weiterhin stetig. Neue Mitglieder, neue Herkunftsstaaten machen die Gesellschaft ###LINENUMBER###vielfältiger und gehen mit neuen kulturellen Hintergründen, Erfahrungen und ###LINENUMBER###biographischen Bezügen ebenso einher, wie mit neuen historischen Bezugspunkte<ins>n</ins> ###LINENUMBER###und einer Verschiebung ihrer Relevanz untereinander. Nicht zuletzt werden die ###LINENUMBER###Menschen, die aktuell nach Deutschland flüchten und zumindest eine Zeit lang ###LINENUMBER###hier bleiben werden, diesen Prozess verstärken.</p>',
            '<p class="deleted">###LINENUMBER###Die Stärkung einer europäischen Identität – ohne die Verwischung historischer ###LINENUMBER###Verantwortung und politischer Kontinuitäten – ist für eine zukünftige ###LINENUMBER###Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen ###LINENUMBER###wird und in der Erinnerungsarbeit aufgegriffen werden muss.</p>',
            '<p><del>###LINENUMBER###Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. </del>###LINENUMBER###Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden ###LINENUMBER###Herausforderungen an:</p>'
        ];
        // Hint: could be further improved, by separating the leading 'n' from the big change block

        $origParagraphs = HTMLTools::sectionSimpleHTML($strPre);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($strPost);
        $diff           = new Diff();
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);

        $this->assertSame($expected, $diffParas);
    }

    /**
     * @throws Internal
     */
    public function testDotAsSeparator(): void
    {
        $origParagraphs = [new SectionedParagraph('<p>wieder<sup>Test</sup>.</p>', 0, 0)];
        $newParagraphs  = [new SectionedParagraph('<p>wieder.</p>', 0, 0)];
        $diff           = new Diff();
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame('<p>wieder<del><sup>Test</sup></del>.</p>', $diffParas[0]);
    }


    public function testStripEmptyLinenumberDels(): void
    {
        $orig   = [
            new SectionedParagraph('<p>###LINENUMBER###<em><strong>Test</strong></em> bla bla bla bla bla lkj bla', 0, 0),
        ];
        $new    = [
            new SectionedParagraph('<p><em><strong>Test</strong></em> bla bla bla bla bla bla', 0, 0),
        ];
        $expect = [
            '<p>###LINENUMBER###<em><strong>Test</strong></em> bla bla bla bla bla <del>lkj </del>bla</p>',
        ];
        $diff   = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $arr = $diff->compareHtmlParagraphs($orig, $new, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $arr);
    }

    public function testDeleteSentenceSecondSentenceBeginningAlike1(): void
    {
        $orig   = [
            new SectionedParagraph('<p>###LINENUMBER###Lorem At vero eos et accusam et justo duo dolores. Lorem ipsum dolor sit amet, ###LINENUMBER###consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et ###LINENUMBER###dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0),
        ];
        $new    = [
            new SectionedParagraph('<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0),
        ];
        $expect = [
            '<p><del>###LINENUMBER###Lorem At vero eos et accusam et justo duo dolores. </del>Lorem ipsum dolor sit amet, ###LINENUMBER###consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et ###LINENUMBER###dolore magna aliquyam erat, sed diam voluptua.</p>',
        ];
        $diff   = new Diff();
        $arr    = $diff->compareHtmlParagraphs($orig, $new, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $arr);
    }

    public function testDeleteSentenceSecondSentenceBeginningAlike2(): void
    {
        $orig   = [
            new SectionedParagraph('<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr. sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0),
        ];
        $new    = [
            new SectionedParagraph('<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr. sed diam Dolor veram Test bla<br>' . "\n<br>\n" . 'sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>', 0, 0),
        ];
        $expect = [
            '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr. <ins>sed diam Dolor veram Test bla<br><br></ins>sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua.</p>',
        ];
        $diff   = new Diff();
        $arr    = $diff->compareHtmlParagraphs($orig, $new, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $arr);
    }

    public function testInsertingIntoEmptySection(): void
    {
        $str1   = '';
        $str2   = '<p>New paragraph</p>';
        $expect = ['<p class="inserted">New paragraph</p>'];

        $diff           = new Diff();
        $origParagraphs = HTMLTools::sectionSimpleHTML($str1);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($str2);
        $diffParas      = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame($expect, $diffParas);
    }

    #[Incomplete('2b will not be added as of yet, because the diff ignores changes in tag values')]
    public function testNumberedSubLists(): void
    {
        $orig = '<ol><li><ol class="lowerAlpha"><li>Point 1</li><li value="3">Point 3</li></ol></li></ol>';
        $new = '<ol><li><ol class="lowerAlpha"><li>Point 1</li><li value="2b">Point 2</li><li value="3">Point 3</li></ol></li></ol>';

        $origParagraphs = HTMLTools::sectionSimpleHTML($orig);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($new);

        $diff = new Diff();
        $out  = $diff->compareHtmlParagraphs($origParagraphs, $newParagraphs, DiffRenderer::FORMATTING_CLASSES);
        $this->assertSame(['<ol start="1"><li><ol class="lowerAlpha"><li>Point 1</li><li value="2b"><ins>Point 2</ins></li><li value="3">Point 3</li></ol></li></ol>'], $out);
    }
}
