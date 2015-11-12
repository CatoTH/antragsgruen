<?php

namespace unit;

use app\components\diff\Diff;
use app\components\diff\Engine;
use app\components\HTMLTools;
use Codeception\Specify;

class DiffTest extends TestBase
{
    use Specify;

    /**
     */
    public function testWordDiff()
    {
        $diff = new Diff();

        $orig = 'Zeichen das sie überwunden';
        $new  = 'Zeichen, dass sie überwunden';
        $this->assertEquals('Zeichen<del> das</del><ins>, dass</ins> sie überwunden', $diff->computeWordDiff($orig, $new));

        $orig = 'Hass';
        $new  = 'Hass, dem Schüren von Ressentiments';
        $this->assertEquals('Hass<ins>, dem Schüren von Ressentiments</ins>', $diff->computeWordDiff($orig, $new));

        $orig = 'Bürger*innen ';
        $new  = 'Menschen ';
        $this->assertEquals('<del>Bürger*innen</del><ins>Menschen</ins> ', $diff->computeWordDiff($orig, $new));

        $orig = 'dekonstruieren.';
        $new  = 'dekonstruieren. Andererseits sind gerade junge Menschen';
        $this->assertEquals('dekonstruieren.<ins> Andererseits sind gerade junge Menschen</ins>', $diff->computeWordDiff($orig, $new));

        $orig = 'dekonstruieren. Andererseits sind gerade junge Menschen';
        $new  = 'dekonstruieren.';
        $this->assertEquals('dekonstruieren.<del> Andererseits sind gerade junge Menschen</del>', $diff->computeWordDiff($orig, $new));

        $orig = 'So viele Menschen wie nie';
        $new  = 'Sie steht vor dieser Anstrengung gemeinsam usw. So viele Menschen wie nie';
        $this->assertEquals('<ins>Sie steht vor dieser Anstrengung gemeinsam usw. </ins>So viele Menschen wie nie', $diff->computeWordDiff($orig, $new));

        $orig = 'Test1 Test 2 der Test 3 Test4';
        $new  = 'Test1 Test 2 die Test 3 Test4';
        $this->assertEquals('Test1 Test 2 <del>der</del><ins>die</ins> Test 3 Test4', $diff->computeWordDiff($orig, $new));

        $orig = '###LINENUMBER###Bildungsbereich. Der Bund muss sie unterstützen. Hier darf das Kooperationsverbot nicht im ###LINENUMBER###Wege stehen.';
        $new  = 'Bildungsbereich.';
        $this->assertEquals('###LINENUMBER###Bildungsbereich.<del> Der Bund muss sie unterstützen. Hier darf das Kooperationsverbot nicht im ###LINENUMBER###Wege stehen.</del>', $diff->computeWordDiff($orig, $new));
    }

    /**
     */
    public function testLinenumberForcelinebreak()
    {
        $orig = '<p>###LINENUMBER###Wir wollen eine Wirtschaftsweise, in der alle Rohstoffe immer wieder neu verarbeitet und ###LINENUMBER###nicht auf einer Deponie landen oder verbrannt werden. Auch die Verschiffung unseres ###LINENUMBER###Elektroschrotts in Entwicklungs- und Schwellenländer ist keine Lösung. Sie verursacht dort ###LINENUMBER###schwere Umweltschäden. Wir wollen deshalb ein Wertstoffgesetz, durch das Herstellern von ###LINENUMBER###Produkten und Verpackungen eine Produktverantwortung zukommt, indem ambitionierte, aber ###LINENUMBER###machbare Recyclingziele eingeführt werden. Dadurch werden Rohstoffpreise befördert, die die ###LINENUMBER###sozialen und ökologischen Folgekosten der Rohstoffgewinnung und ihrer Verwertung am Ende des ###LINENUMBER###Produktlebenszyklus und gegenüber den Verbraucher*innen ehrlich abbilden. So wird der ###LINENUMBER###Einsatz von Recyclingmaterial gegenüber Primärmaterial wettbewerbsfähig. Wir setzen uns ###LINENUMBER###dafür ein, dass für gewerbliche Abfälle und Bauabfälle die gleichen ökologischen ###LINENUMBER###Anforderungen gelten wie für die Hausmüllsammlung und -verwertung.</p>';
        $new  = '<p>Der beste Abfall ist der, der nicht entsteht. Wir wollen eine Wirtschaftsweise, in der Material- und Rohstoffeffizienz an erster Stelle stehen und in der alle Rohstoffe immer wieder neu verarbeitet werden und nicht auf einer Deponie landen, in Entwicklungs- und Schwellenländer exportiert oder verbrannt werden. Wir setzen uns für echte Kreislaufwirtschaft mit dem perspektivischen Ziel von „Zero Waste“ ein und wollen den Rohstoffschatz, der im vermeintlichen Müll schlummert heben. Wir wollen deshalb ein Wertstoffgesetz, durch das Herstellern von###FORCELINEBREAK###Produkten und Verpackungen eine ökologische Produktverantwortung zukommt, indem ambitionierte, abermachbare Recyclingziele sowie Ziele zur Material- und Rohstoffeffizienz eingeführt werden. Wir wollen einen „Recycling-Dialog“ mit Industrie, Verbraucher- und Umweltverbänden sowie der Abfallwirtschaft ins Leben rufen, um gemeinsam ambitioniertere Standards in Bezug auf weniger Rohstoffeinsatz und mehr Recycling zu entwickeln und Anreize für die Verwendung von Recyclingmaterialien zu schaffen.</p>
<p>Wir setzen uns dafür ein, dass die Rohstoffpreise die###FORCELINEBREAK###sozialen und ökologischen Folgekosten der Rohstoffgewinnung und ihrer Verwertung am Ende des###FORCELINEBREAK###Produktlebenszyklus und gegenüber den Verbraucher*innen ehrlich abbilden. So wird Ökologie zum Wettbewerbsvorteil: Wer weniger Rohstoffe verbraucht oder Recyclingmaterial anstattPrimärmaterial, spart Geld, Damit der gesamte (Sekundär)-Rohstoffschatz gehoben werden kann, setzen wir uns außerdem dafür ein , dass für gewerbliche Abfälle und Bauabfälle die gleichen ökologischen###FORCELINEBREAK###Anforderungen gelten wie für die Hausmüllsammlung und -verwertung.</p>';

        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $diff->setFormatting(Diff::FORMATTING_CLASSES);
        $out = $diff->computeDiff($orig, $new);

        $expect = '<p><ins>Der beste Abfall ist der, der nicht entsteht. </ins>###LINENUMBER###Wir wollen eine Wirtschaftsweise, <ins>in der Material- und Rohstoffeffizienz an erster Stelle stehen und </ins>in der alle Rohstoffe immer wieder neu verarbeitet <ins>werden </ins>und ###LINENUMBER###nicht auf einer Deponie landen<del> oder verbrannt werden. Auch die Verschiffung unseres ###LINENUMBER###Elektroschrotts</del><ins>,</ins> in Entwicklungs- und Schwellenländer <del>ist keine Lösung. Sie verursacht dort ###LINENUMBER###schwere Umweltschäden</del><ins>exportiert oder verbrannt werden. Wir setzen uns für echte Kreislaufwirtschaft mit dem perspektivischen Ziel von „Zero Waste“ ein und wollen den Rohstoffschatz, der im vermeintlichen Müll schlummert heben</ins>. Wir wollen deshalb ein Wertstoffgesetz, durch das Herstellern von<ins>###FORCELINEBREAK###</ins>###LINENUMBER###Produkten und Verpackungen eine <ins>ökologische </ins>Produktverantwortung zukommt, indem ambitionierte, <del>aber ###LINENUMBER###machbare</del><ins>abermachbare</ins> Recyclingziele <ins>sowie Ziele zur Material- und Rohstoffeffizienz </ins>eingeführt werden. <del>Dadurch werden Rohstoffpreise befördert,</del><ins>Wir wollen einen „Recycling-Dialog“ mit Industrie, Verbraucher- und Umweltverbänden sowie der Abfallwirtschaft ins Leben rufen, um gemeinsam ambitioniertere Standards in Bezug auf weniger Rohstoffeinsatz und mehr Recycling zu entwickeln und Anreize für</ins> die <ins>Verwendung von Recyclingmaterialien zu schaffen.</p>
<p>Wir setzen uns dafür ein, dass </ins>die ###LINENUMBER###<ins>Rohstoffpreise die###FORCELINEBREAK###</ins>sozialen und ökologischen Folgekosten der Rohstoffgewinnung und ihrer Verwertung am Ende des<ins>###FORCELINEBREAK###</ins>###LINENUMBER###Produktlebenszyklus und gegenüber den Verbraucher*innen ehrlich abbilden. So wird <del>der ###LINENUMBER###Einsatz von</del><ins>Ökologie zum Wettbewerbsvorteil: Wer weniger Rohstoffe verbraucht oder</ins> Recyclingmaterial <del>gegenüber Primärmaterial wettbewerbsfähig. Wir</del><ins>anstattPrimärmaterial, spart Geld, Damit der gesamte (Sekundär)-Rohstoffschatz gehoben werden kann,</ins> setzen <ins>wir </ins>uns <ins>außerdem </ins>###LINENUMBER###dafür ein<ins> </ins>, dass für gewerbliche Abfälle und Bauabfälle die gleichen ökologischen<ins>###FORCELINEBREAK###</ins>###LINENUMBER###Anforderungen gelten wie für die Hausmüllsammlung und -verwertung.</p>';
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testDeleteMultipleParagraphs()
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

        $origParagraphs = HTMLTools::sectionSimpleHTML($orig);
        $newParagraphs  = HTMLTools::sectionSimpleHTML($new);

        $diff = new Diff();
        $out  = $diff->computeAmendmentParagraphDiffInt($origParagraphs, $newParagraphs, 1, 80, null, null);

        $this->assertEquals('<p><del>Etwas Text in P</del></p>' . "\n", $out[1]->strDiff);
        $this->assertEquals('<ul><li>Eine <del>erste </del><ins><em>erste</em> </ins>Zeile</li></ul>' . "\n", $out[2]->strDiff);
        $this->assertEquals('<ul><li>Zeile 2 mit <ins>sdff </ins>etwas mehr</li></ul>' . "\n", $out[3]->strDiff);
        $this->assertEquals('<ul><li><del>Ganz was anderes</del></li></ul>' . "\n", $out[4]->strDiff);
        $this->assertEquals('<ul><li>Noch <ins>sdfsd </ins>eine Zeile mit etwas Text</li></ul>' . "\n", $out[5]->strDiff);
    }

    /**
     */
    public function testSwitchAndInsertListItems()
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
        $out  = $diff->computeAmendmentParagraphDiffInt($origParagraphs, $newParagraphs, 1, 80, null, null);

        $this->assertEquals('<del><p>Die Stärkung einer europäischen Identität – ohne die Verwischung historischer Verantwortung und politischer Kontinuitäten – ist für eine zukünftige Erinnerungspolitik ein wesentlicher Aspekt, der auch Erinnerungskulturen prägen wird und in der Erinnerungsarbeit aufgegriffen werden muss.</p></del>', trim($out[0]->strDiff));
        $this->assertEquals('<p><del>Gleiches gilt für die Jugendverbände und –ringe als Teil dieser Gesellschaft. </del>Wir als Jugendverbände und –ringe im DBJR nehmen uns der sich daraus ergebenden Herausforderungen an:</p>', trim($out[1]->strDiff));
        $this->assertEquals('<ul class="deleted"><li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li></ul>', trim($out[2]->strDiff));
        $this->assertEquals('<ul><li>Wir Jugendverbände sehen uns in der Verantwortung, das Gedenken an den Holocaust und die nationalsozialistischen Verbrechen, die von Deutschland ausgingen, wach zu halten und gemeinsam Sorge dafür zu tragen, „dass Auschwitz nie wieder sei!“.</li></ul><ul class="inserted"><li>Wir stellen uns immer wieder neu der Frage, wie Jugendverbände der zunehmenden kulturellen Vielfalt in ihrer verbandlichen Erinnerungskultur und ihrer Erinnerungsarbeit gerecht werden und gleichzeitig die jeweils eigene, auch kulturelle Identität, die den Verband und seine Attraktivität ausmacht, wahren können.</li></ul>', trim($out[3]->strDiff));
    }

    /**
     */
    public function testReplaceListByP()
    {
        $orig      = ['<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>'];
        $amend     = ['<p>Test 456</p>'];
        $diff      = new Diff();
        $diffParas = $diff->computeAmendmentParagraphDiffInt($orig, $amend, 1, 80, null, null);
        $diffStr   = $diff->cleanupDiffProblems($diffParas[0]->strDiff);
        $expected  = '<ul class="deleted"><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li><li>Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand vui huift vui woaß?</li><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<p><ins>Test 456</ins></p>
';
        $this->assertEquals($expected, $diffStr);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testReplaceParagraph()
    {
        $str1   = '<p>Unchanging line</p>
<p>Das wollen wir mit unserer Zeitpolitik ermöglichen. Doch wie die Aufgaben innerhalb der Familie verteilt werden, ' .
            'entscheidet sich heute oft in ernüchternder Weise: Selbst wenn Paare gleichberechtigt und in ' .
            'gegenseitigem Einvernehmen die Rollenverteilung miteinander ausmachen wollen, scheitern sie zu oft ' .
            'an der Realität – und leben plötzlich Rollenbilder, die sie eigentlich so nie wollten. ' .
            'Verkrustete Strukturen und Fehlanreize regieren in ihr Leben hinein; sie verhindern, dass Frauen und ' .
            'Männer selbstbestimmt und auf Augenhöhe ihre Entscheidungen treffen können.</p>';
        $str2   = '<p>Unchanging line</p>
<p>Diesen Wunsch der Paare in die Realität umzusetzen ist das Ziel unserer Zeitpolitik. Hierfür sind verkrustete ' .
            'patriarchalische Strukturen und Fehlanreize abzubauen, jedoch ohne dass neuer sozialer Druck auf ' .
            'Familien entsteht. Damit Paare selbstbestimmt und auf Augenhöhe die Rollenverteilung in ihrer Familie ' .
            'festlegen können, muss die Gesellschaft die Entscheidungen der Familien unabhängig von ihrem Ergebnis ' .
            'akzeptieren und darf keine Lebensmodelle stigmatisieren.</p>';
        $expect = '<p>Unchanging line</p>
<p><del>Das wollen wir mit unserer Zeitpolitik ermöglichen. Doch wie die Aufgaben innerhalb der Familie verteilt werden, entscheidet sich heute oft in ernüchternder Weise: Selbst wenn Paare gleichberechtigt und in gegenseitigem Einvernehmen die Rollenverteilung miteinander ausmachen wollen, scheitern sie zu oft an der Realität – und leben plötzlich Rollenbilder, die sie eigentlich so nie wollten. Verkrustete Strukturen und Fehlanreize regieren in ihr Leben hinein; sie verhindern, dass Frauen und Männer selbstbestimmt und auf Augenhöhe ihre Entscheidungen treffen können.</del>
<ins>Diesen Wunsch der Paare in die Realität umzusetzen ist das Ziel unserer Zeitpolitik. Hierfür sind verkrustete patriarchalische Strukturen und Fehlanreize abzubauen, jedoch ohne dass neuer sozialer Druck auf Familien entsteht. Damit Paare selbstbestimmt und auf Augenhöhe die Rollenverteilung in ihrer Familie festlegen können, muss die Gesellschaft die Entscheidungen der Familien unabhängig von ihrem Ergebnis akzeptieren und darf keine Lebensmodelle stigmatisieren.</ins></p>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $out  = $diff->cleanupDiffProblems($out);

        $this->assertEquals($expect, $out);


        $str1   = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird Und noch etwas am Ende. Und noch etwas am Ende</p>';
        $str2   = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen Und noch etwas am Ende. Und noch etwas am Ende</p>';
        $expect = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. <del>Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird Und noch etwas am Ende.</del>
<ins>Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen Und noch etwas am Ende.</ins> Und noch etwas am Ende</p>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $out  = $diff->cleanupDiffProblems($out);

        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testDeletedSentenceAtEnd()
    {
        $str1   = '<p>gesellschaftlich dominante Narrative zu hinterfragen und ggf. zu dekonstruieren. Andererseits sind gerade junge Menschen auf für sie geeignete Möglichkeiten und Wege des Gedenkens angewiesen, da sie selbst noch weniger über persönliche Erinnerungen verfügen und dennoch bereits den legitimen Anspruch auf Mitbestimmung haben. Wer Gesellschaft mitgestalten will, muss (also) erinnern können.</p>';
        $str2   = '<p>gesellschaftlich dominante Narrative zu hinterfragen und ggf. zu dekonstruieren.</p>';
        $expect = '<p>gesellschaftlich dominante Narrative zu hinterfragen und ggf. zu dekonstruieren.<del> Andererseits sind gerade junge Menschen auf für sie geeignete Möglichkeiten und Wege des Gedenkens angewiesen, da sie selbst noch weniger über persönliche Erinnerungen verfügen und dennoch bereits den legitimen Anspruch auf Mitbestimmung haben. Wer Gesellschaft mitgestalten will, muss (also) erinnern können.</del></p>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $out  = $diff->cleanupDiffProblems($out);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testParagraphs()
    {
        $str1 = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>' . "\n" . '<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';
        $str2 = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachsdfsdfsdf helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';

        $expect = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. <del>Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</del></p>' . "\n" .
            '<p><del>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds</del>' . "\n" .
            '<ins>Biaschlegl soi oans, zwoa, gsuffa Oachsdfsdfsdf</ins> helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $out  = $diff->cleanupDiffProblems($out);
        $this->assertEquals($expect, $out);


        $str1   = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4 Str5';
        $str2   = 'Str1 Str2 Str3 Str4';
        $expect = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4<del> Str5</del>';

        $diff = new Diff();
        $diff->setIgnoreStr('###LINEBREAK###');
        $out = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'Abcdef abcdef Abcdef AAbcdef Abcdef';
        $str2   = 'Abcdef abcdefghi Abcdef AAbcdef preAbcdef';
        $expect = 'Abcdef abcdef<ins>ghi</ins> Abcdef AAbcdef <ins>pre</ins>Abcdef';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'ym Bla gagen lerd mal';
        $str2   = 'ym Blagagen lerd mal';
        $expect = 'ym Bla<del> </del>gagen lerd mal';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'uns dann als Zeichen das sie uns überwunden hatten';
        $str2   = 'uns dann als Zeichen, dass sie uns überwunden hatten';
        $expect = 'uns dann als Zeichen<del> das</del><ins>, dass</ins> sie uns überwunden hatten';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'Test <strong>Test1</strong> Test2';
        $str2   = 'Test <strong>Test2</strong> Test2';
        $expect = 'Test <strong>Test<del>1</del><ins>2</ins></strong> Test2';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testTwoInserts()
    {
        $str1   = '<ul><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<p>###LINENUMBER###I waar soweid Blosmusi es nomoi.</p>';
        $str2   = '<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<ul><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>
<ul><li>Blabla</li></ul>
<p>I waar soweid Blosmusi es nomoi.</p>';
        $expect = '<ul><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>
<ul class="inserted"><li>Blabla</li></ul>
<p>###LINENUMBER###I waar soweid Blosmusi es nomoi.</p>';

        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $diff->setFormatting(0);
        $out = $diff->computeDiff($str1, $str2);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testGroupOperations()
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
        $grouped = $diff->groupOperations($src, Diff::ORIG_LINEBREAK);
        $this->assertEquals($src, $grouped); // Should not be changed


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
        $this->assertEquals($expected, $out);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testTwoChangedLis()
    {
        $str1   = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li></ul>
<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';
        $str2   = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.asdasd</li></ul>
<ul><li>aWoibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';
        $expect = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.<ins>asdasd</ins></li></ul>
<ul><li><ins>a</ins>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testShiftMisplacedTags()
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
        $this->assertEquals($orig, $corrected);


        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['Old ', Engine::DELETED],
            ['deleted ', Engine::DELETED],
            ['normal ', Engine::UNMODIFIED],
            [' text', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertEquals($orig, $corrected);


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
        $this->assertEquals([
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
        $this->assertEquals([
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

    /**
     */
    public function testLineDiffWithTags()
    {
        $strPre   = '<ul><li>Listenpunkt</li></ul>';
        $strPost  = '<p>Test</p>';
        $diff     = new Diff();
        $out      = $diff->computeLineDiff($strPre, $strPost);
        $out      = $diff->cleanupDiffProblems($out);
        $expected = '<ul><li><del>Listenpunkt</del></li></ul><p><ins>Test</ins></p>';
        $this->assertEquals($expected, $out);
    }

    /**
     */
    public function testParagraphManyChanges()
    {
        $strPre  = '<p>###LINENUMBER###Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten ###LINENUMBER###sozialen Absicherung. Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen ###LINENUMBER###Renten-, Kranken- und Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus ist ###LINENUMBER###es in der Anfangsphase der Selbständigkeit und insbesondere bei Start-ups oft schwierig, die ###LINENUMBER###vollen Beitragslasten zu tragen. Wir wollen an Lösungen arbeiten, die angelehnt an den ###LINENUMBER###Gedanken der Künstlersozialkasse, für eine temporäre Unterstützung an dieser Stelle sorgen. ###LINENUMBER###Damit sich Gründer*innen leichter am Markt etablieren können, wollen wir den bürokratischen ###LINENUMBER###Aufwand senken. Eine einzige Anlaufstelle (One-Stop-Shop) würde ihre Situation deutlich ###LINENUMBER###verbessern. Hier sollen sämtliche Beratungsleistungen und bürokratische Anforderungen ###LINENUMBER###abwickelt werden, damit sie nicht im Behördendschungel aufgehalten werden.</p>';
        $strPost = '<p>Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. <em>Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus wollen wir eine Bürger*innenversicherung in Gesundheit und Pflege einführen. Auch die Rentenversicherung wollen wir schrittweise zu einer Bürger*innenversicherung weiterentwickeln. In einem ersten Schritt wollen wir die bisher nicht pflichtversicherten Selbständigen in die gesetzliche Rentenversicherung einbeziehen. Die Grüne Garantierente soll ein Signal speziell an Selbständige mit geringem Einkommen senden, dass sich die Beiträge zur Rentenversicherung auch lohnen. </em> Damit sich Gründer*innen leichter am Markt etablieren können, wollen wir den bürokratischen Aufwand senken. Eine einzige Anlaufstelle (One-Stop-Shop) würde ihre Situation deutlich verbessern. Hier sollen sämtliche Beratungsleistungen und bürokratische Anforderungen abwickelt werden, damit sie nicht im Behördendschungel aufgehalten werden.</p>';
        $diff    = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $out      = $diff->computeDiff($strPre, $strPost);
        $expected = '<p>###LINENUMBER###Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten sozialen Absicherung. Ein weiteres wichtiges Hemmnis für Gründungen sind Existenzsorgen aufgrund einer schlechten ###LINENUMBER###sozialen Absicherung. <del>Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen ###LINENUMBER###Renten-, Kranken- und Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus ist ###LINENUMBER###es in der Anfangsphase der Selbständigkeit und insbesondere bei Start-ups oft schwierig, die ###LINENUMBER###vollen Beitragslasten zu tragen. Wir wollen an Lösungen arbeiten, die angelehnt an den ###LINENUMBER###Gedanken der Künstlersozialkasse, für eine temporäre Unterstützung an dieser Stelle sorgen. </del>
<ins><em>Daher wollen wir, dass der Zugang für Selbständige zur freiwilligen Arbeitslosenversicherung umgehend verbessert wird. Darüber hinaus wollen wir eine Bürger*innenversicherung in Gesundheit und Pflege einführen. Auch die Rentenversicherung wollen wir schrittweise zu einer Bürger*innenversicherung weiterentwickeln. In einem ersten Schritt wollen wir die bisher nicht pflichtversicherten Selbständigen in die gesetzliche Rentenversicherung einbeziehen. Die Grüne Garantierente soll ein Signal speziell an Selbständige mit geringem Einkommen senden, dass sich die Beiträge zur Rentenversicherung auch lohnen. </em> </ins>###LINENUMBER###Damit sich Gründer*innen leichter am Markt etablieren können, wollen wir den bürokratischen ###LINENUMBER###Aufwand senken. Eine einzige Anlaufstelle (One-Stop-Shop) würde ihre Situation deutlich ###LINENUMBER###verbessern. Hier sollen sämtliche Beratungsleistungen und bürokratische Anforderungen ###LINENUMBER###abwickelt werden, damit sie nicht im Behördendschungel aufgehalten werden.</p>';
        $this->assertEquals($expected, $out);
    }

    /**
     */
    public function testShortParagraph()
    {
        $strPre   = '<p><strong>Balance von Freiheit und Sicherheit für Solo-Selbstständige und Existenzgründer*innen</strong></p>';
        $strPost  = '<p><strong>Balance von Freiheit und Sicherheit für Selbstständige und Existenzgründer*innen</strong></p>';
        $diff     = new Diff();
        $out      = $diff->computeDiff($strPre, $strPost);
        $expected = '<p><strong>Balance von Freiheit und Sicherheit für <del>Solo-</del>Selbstständige und Existenzgründer*innen</strong></p>';
        $this->assertEquals($expected, $out);
    }

    /**
     */
    public function testDeleteBeyondList()
    {
        $strPre  = "<p>###LINENUMBER###Test.</p>
<p>###LINENUMBER###<strong>To be deletedgi: </strong></p>
<ul><li>###LINENUMBER###Test 2</li></ul>
<ul><li>###LINENUMBER###Test 1</li></ul>
<p>###LINENUMBER###Also to be deleted.</p>";
        $strPost = "<p>Test.</p>";
        $diff    = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $out      = $diff->computeDiff($strPre, $strPost);
        $expected = "<p>###LINENUMBER###Test.</p>
<p><del>###LINENUMBER###</del><strong><del>To be deletedgi: </del></strong></p>
<ul><li><del>###LINENUMBER###Test 2</del></li></ul>
<ul><li><del>###LINENUMBER###Test 1</del></li></ul>
<p><del>###LINENUMBER###Also to be deleted.</del></p>";
        $this->assertEquals($expected, $out);
    }

    /**
     */
    public function testLiPSomething()
    {
        // From https://bdk.antragsgruen.de/39/motion/133/amendment/323
        $strPre = '<ul><li>###LINENUMBER###Die Mobilisierung der Mittel für den internationalen Klimaschutz ist eine ###LINENUMBER###öffentliche Aufgabe.</li></ul>';
        $strPost   = '<ul><li><p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>' . "\n" . '.</li></ul>';
        $expect = '<ul><li><p>###LINENUMBER###Die Mobilisierung der Mittel für den internationalen Klimaschutz ist <del>eine</del><ins>zum allergroßten Teil</ins> ###LINENUMBER###öffentliche Aufgabe<ins>, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen</ins>.</p><ins>.</ins></li></ul>';

        $diff    = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $out      = $diff->computeDiff($strPre, $strPost);
        $out = $diff->cleanupDiffProblems($out);
        $this->assertEquals($expect, $out);

    }
}
