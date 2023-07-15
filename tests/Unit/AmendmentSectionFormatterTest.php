<?php

namespace Tests\Unit;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DataTypes\AffectedLineBlock;
use app\components\diff\DiffRenderer;
use app\models\sectionTypes\TextSimple;
use Codeception\Attribute\Incomplete;
use Tests\Support\Helper\TestBase;

class AmendmentSectionFormatterTest extends TestBase
{
    private static function getAffectedLinesBlock(int $from, int $to, string $text): AffectedLineBlock
    {
        $lines = new AffectedLineBlock();
        $lines->text = $text;
        $lines->lineFrom = $from;
        $lines->lineTo = $to;

        return $lines;
    }

    public function testKeepOLStart(): void
    {
        $strPre  = '<ol><li>Test 1</li><li>Test 2</li><li>Test 3</li><li>Test 4</li></ol>';
        $strPost = '<ol><li>Test 1</li><li>Test 2</li><li>Test 3neu</li><li>Test 4</li></ol>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_CLASSES);

        $this->assertEquals([
            self::getAffectedLinesBlock(3, 3, '<ol start="3"><li value="3">###LINENUMBER###Test <del>3</del><ins>3neu</ins></li></ol>'),
        ], $diffGroups);
    }

    public function testOverlongLines(): void
    {
        $orig      = '<p>[1] <a href="https://www.gruene.de/fileadmin/user_upload/Dokumente/Beschl%C3%BCsse/Humanitaeren_Zuzug_von_Roma_aus_Balkanstaaten_ermoeglichen.pdf">https://www.gruene.de/fileadmin/user_upload/Dokumente/Beschl%C3%BCsse/Humanitaeren_Zuzug_von_Roma_aus_Balkanstaaten_ermoeglichen.pdf</a></p>';
        $new       = $orig;
        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($orig);
        $formatter->setTextNew($new);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(92, DiffRenderer::FORMATTING_INLINE);
        $this->assertCount(0, $diffGroups);
    }

    public function testRemoveWhitespaces(): void
    {
        $orig = '<p>Der eigene, existenzsichernde Job ist immer noch die beste Absicherung gegen Armut. Häufig ist der Weg dorthin aber für Alleinerziehende und gering verdienende Eltern sehr schwierig. Deswegen sind sie in besonderem Maße auf verlässliche und gute Betreuungs- und Bildungsangebote für ihre Kinder angewiesen. Aus- und Weiterbildungen in Teilzeit können ein Weg für Alleinerziehende sein, wieder einen existenzsichernden Arbeitsplatz zu finden. Dabei muss gewährleistet sein, dass in diesen Phasen das Existenzminimum von Alleinerziehenden und ihren Kindern ohne großen bürokratischen Aufwand durch lückenlose Leistungen gesichert ist. <strong>Wiedereinstiegshilfen nach der Babypause</strong> oder einer längeren Elternzeit wollen wir <strong>verbessern</strong>.</p>';
        $new  = '<p>Der eigene, existenzsichernde Job ist immer noch die beste Absicherung gegen Armut. Häufig ist der Weg dorthin aber für Alleinerziehende und gering verdienende Eltern sehr schwierig. Deswegen sind sie in besonderem Maße auf verlässliche, kostenlose und gute Betreuungs- und Bildungsangebote für ihre Kinder angewiesen. Aus- und Weiterbildungen in Teilzeit können ein Weg für Alleinerziehende sein, wieder einen existenzsichernden Arbeitsplatz zu finden. Dabei muss gewährleistet sein, dass in diesen Phasen das Existenzminimum von Alleinerziehenden und ihren Kindern ohne großen bürokratischen Aufwand durch lückenlose Leistungen gesichert ist. <strong>Wiedereinstiegshilfen nach der Babypause</strong> oder einer längeren Elternzeit wollen wir <strong>verbessern</strong>.</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($orig);
        $formatter->setTextNew($new);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(92, DiffRenderer::FORMATTING_INLINE);
        $this->assertCount(1, $diffGroups);

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($orig);
        $formatter->setTextNew($new);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(92, DiffRenderer::FORMATTING_CLASSES);
        $this->assertCount(1, $diffGroups);
    }

    public function testCrashing(): void
    {
        // This basically tests if the cache in ArrayMatcher's calcSimilarity does its job.
        // If it doesn't, this calculation will take ages

        $strPre = '<p>Für die im Deutschen Bundesjugendring (DBJR) zusammengeschlossenen Jugendverbände steht fest: Wir fordern die uneingeschränkte Solidarität mit flüchtenden Menschen, insbesondere mit Kindern und Jugendlichen, die nach Europa und Deutschland kommen. Niemand flüchtet freiwillig aus seiner Heimat. Fluchtgeschichten sind oftmals Geschichten von Krieg und Gewalt, Diskriminierung, Verfolgung oder Unterdrückung sowie schweren Menschenrechtsverletzungen. Es sind aber auch Geschichten von wirtschaftlichem oder sozialem Elend, Krankheiten – vor allem aber von Verzweiflung und oftmals auch dem Gefühl, nicht willkommen zu sein.</p>
<p>Eine wirksame Flüchtlings- und Migrationspolitik beginnt für uns bereits an den (Außen-)Grenzen der Europäischen Union und kann nur gemeinsam und solidarisch durch alle EU-Mitgliedsstaaten gestaltet werden!</p>
<p>Junge Europäer_innen wollen keine "Festung Europa", sondern ein offenes, tolerantes Europa, für das eine konzertierte Aufnahme von flüchtenden Menschen in Not selbstverständlich ist. Die Europäische Union muss dafür sorgen, dass Menschen in Sicherheit fliehen können. Dazu gehört eine geregelte Einreisemöglichkeit: Damit Schutzsuchende überhaupt einen sicheren Weg nach Europa finden, müssen die völkerrechtswidrigen „Push-Backs“ an den südlichen EU-Landgrenzen (v.a. Bulgarien, Griechenland, Ceuta und Mellila) abgebaut werden. Grenzzäune die - wie z.B. in Ungarn - neu errichtet werden, sind in einem Europa ohne Grenzen nicht hinnehmbar. Kurzfristig von einzelnen Mitgliedsstaaten des Schengenraums wiedereingeführte innereuropäische Grenzkontrollen verstoßen gegen geltendes EU-Recht und sind sofort auszusetzen. Sie zeigen auch, dass die Außengrenzen der EU nicht den vereinbarten Sicherheitsstandards genügen und die Sicherheit der Flüchtlinge gefährden. Der Status Quo fördert den gefährlichen, oft tödlichen Menschenschmuggel und steht diametral zu einer der zentralen Errungenschaften der europäischen Integration: Der grenzenlosen Reisefreiheit.</p>
<p>Nur die zwingende Einhaltung menschenrechtlicher Standards und sichere Fluchtrouten eröffnen Flüchtlingen den Zugang zum Territorium der EU. Dafür bedarf es einer engeren Kooperation mit den Ländern, die von den Flüchtlingsströmen besonders betroffen sind sowie dem Flüchtlingshilfswerk der Vereinten Nationen (UNHCR). Die EU muss sich hier solidarisch zeigen, und diese Länder in ihrer Situation entlasten. Dies könnte auch dazu führen, die Anzahl der Flüchtlinge, die die lebensgefährliche Fahrt über das Mittelmeer antreten, zu verringern. Solange Menschen allerdings diesen Weg als ihre einzige Möglichkeit sehen, der Situation aus ihrem Herkunftsland zu entfliehen, ist Solidarität mit den Bootsflüchtlingen geboten. Die EU sollte deshalb eine zivile, europäische Seenotrettung etablieren und Frontex von dieser Aufgabe entbinden. Die Sicherung der EU-Außengrenzen darf von der EU bzw. von einzelnen Nationalstaaten nicht zum „Mauerbau“ um Europa instrumentalisiert werden und muss Gemeinschaftsaufgabe sein.</p>
<p>Die Praxis, immer mehr Staaten zu sicheren Drittstaaten zu erklären, ist aus unserer Sicht sehr zweifelhaft. Sofern sich die EU vorbehält, sichere Herkunftsstaaten zu beschließen, fordern wir dabei insbesondere auch die Situation für LGBT*s in den Herkunftsländern zu berücksichtigen. Sie sind durch die Genfer Konvention nicht eindeutig geschützt und werden deshalb häufig bei dieser Entscheidung übergangen. Länder, in welchen LGBT*s rechtlich verfolgt oder vom Staat nicht vor der Willkür der Bevölkerung geschützt werden, dürfen nicht als sichere Herkunftsländer gelten. Nach wie vor werden in Europa Minderheiten unterdrückt, verfolgt und nur unzureichend geschützt. Hier gilt es generell weiterhin umfangreiche Einzelfallprüfungen zu ermöglichen, Ziel muss allerdings die Toleranz und Akzeptanz dieser Menschen sein.</p>
<p>Durch die Beibehaltung des Dublin-Verfahrens und seiner Erweiterung auf alle Personen, die um internationalen Schutz ersuchen, wird faktisch den südlichen EU-Staaten (insbesondere Malta, Italien, Spanien und Griechenland) eine größere Verpflichtung auferlegt als nördlicheren Ländern. Die Abschaffung der Dublin-Regelungen und ein solidarischer Neuanfang in der europäischen Asyl- und Flüchtlingspolitik gemäß des Artikel 78 im VAEU, insbesondere Absatz 2c), sind deswegen unabdingbar!</p>
<p>Wir fordern, zügig und dauerhaft einen verbindlichen und gerechten EU-Verteilungsschlüssel zu etablieren, der die Bevölkerungszahl und die Wirtschaftskraft des jeweiligen Mitgliedsstaats berücksichtigt. Alle Mitgliedsstaaten sind hier gleichermaßen in der Pflicht - eine Kategorisierung nach Herkunft und Religion, die damit einhergehende Stigmatisierung und die Möglichkeit des "Freikaufens" lehnen wir ab. Notleidende Menschen sind keine Ware!</p>
<p>Die Etablierung sogenannter Hotspots an den Außengrenzen der EU ist in der aktuellen Situation ein adäquates Instrument um Schutzsuchenden eine erste Zuflucht zu bieten und eine Registrierung vorzunehmen. Sicherzustellen sind dabei menschenwürdige Zustände in den Auffangstätten unter Beachtung verbindlicher, europaweit geltender Standards und die rasche Verteilung auf alle EU-Mitgliedsstaaten. Die Einrichtung der HotSpots ist als eine Gemeinschaftsaufgabe der Europäischen Union zu sehen und durch die EU-Kommission umzusetzen.</p>
<p>Bei der Weiterentwicklung des Verteilungssystems der Flüchtlinge auf die einzelnen EU-Mitgliedsländer ist es wünschenswert, dass auch individuelle Faktoren, wie die Familienzusammenführung und Sprachkenntnisse, Berücksichtigung finden. Im Sinne der Genfer Flüchtlingskonvention fordern wir perspektivisch, Flüchtlingen die Wahl des für ihr Asylverfahren zuständigen Staates selbst zu überlassen. Dies könnte beispielsweise dadurch realisiert werden, dass die finanzielle Verantwortung durch einen gesamteuropäischen Fond getragen wird.</p>
<p>Mittelfristig fordern wir eine gemeinsame europäische Einwanderungspolitik. Es muss möglich sein, die EU auf legalem Wege zu erreichen. Wir fordern die EU-Staaten auf, endlich ein einheitliches Verfahren innerhalb der EU anzubieten, das allen Flüchtlingen faire Chancen und eine Grundversorgung nach gemeinsam definierten Standards bietet. EU-Visa aufgrund humanitärer Dringlichkeit sollten ebenfalls eine Option darstellen. Wir fordern zudem eine deutliche Aufstockung des Botschaftspersonals in Irak, Libanon, Türkei und Jordanien, damit Frauen und Kinder, die dort auf den ihnen zustehenden Familiennachzug warten, nicht über Jahre in lebensbedrohlicher und prekärer Situation ausharren müssen.</p>
<p>Da die Probleme, die zur derzeitigen Massenflucht führen, kurzfristig nicht zu lösen sind, ist es unerlässlich, den geflüchteten Menschen die Integration in die europäische Gesellschaft zu ermöglichen. Einerseits, um vorübergehende Perspektiven für sie zu entwickeln, aber auch um im Bedarfsfall die vollständige und dauerhafte Aufnahme zu erleichtern. Gerade vor der Fragestellung des Demographischen Wandels und des Bevölkerungsrückgangs in Europa sind die Flüchtlingsströme eine große Chance.</p>
<p>Für eine erfolgreiche Integration sind neben einer europäischen Willkommenskultur die non-formalen und formalen Erasmus+ Bildungsprogramme der EU wichtige Instrumente, die es auszubauen gilt. Die inhaltliche Schwerpunktsetzung dieser Programme muss sich jedoch flexibel an den gewandelten Anforderungen orientieren und auf Integration durch Begegnung und Verständigung setzen. Auch der Abbau von Vorurteilen, der Kampf gegen den Rassismus, gegen antidemokratische Strömungen und den demagogischen Rechtspopulismus in Europa, müssen vorrangige Bildungsziele werden. Um den alten und neuen Aufgaben gerecht zu werden, ist eine höhere Investition in die EU-Bildungsprogramme dringend nötig.</p>
<p>Verantwortung zu übernehmen, heißt aber auch eine Politik und Wirtschaft zu betreiben, die nicht zu Lasten der Länder geht, aus denen gerade viele Menschen fliehen. Der Schlüssel zur Lösung der Flüchtlingskatastrophe liegt deshalb nicht nur in der Europäischen Union, sondern auch in den Herkunftsländern der Flüchtlinge. Waffenexporte in diese Regionen sind entschieden abzulehnen. Solange es dort weiterhin keine menschenwürdigen Lebensperspektiven gibt, wird der Flüchtlingsstrom nicht abreißen. Wir fordern die Europäische Union und die Bundesregierung dazu auf, diese Länder aktiv bei demokratischen Reformen und einer nachhaltigen Entwicklung zu unterstützen.</p>
<p>Europa und die Europäische Union basieren auf einem Wertekanon - Menschenwürde, Freiheit, Demokratie, Gleichheit, Rechtsstaatlichkeit und die Achtung der Menschenrechte nehmen darin eine zentrale Rolle ein. Diese Werte gilt es zu schützen und zu teilen - Solidarität ist allerdings keine Einbahnstraße. Mehrheitsbeschlüsse in den Institutionen sind dabei genauso zu akzeptieren und anzuwenden, wie Sanktionsinstrumente und Vertragsstrafen. Nur so hat dieser Kontinent eine friedliche Zukunft und wird seinen ethischen Ansprüchen gerecht.</p>
<p>Wir sind uns bewusst, dass die momentane Situation vermutlich eine der größten Herausforderungen dieser Generation ist und viel Kraft kostet. Wir sind jedoch überzeugt, dass es sich lohnt und fordern deshalb nicht weniger, sondern mehr Europa - sozial, solidarisch und wider den nationalstaatlichen Egoismen!</p>';

        $strPost = '<p>Die im Deutschen Bundesjugendring (DBJR) zusammengeschlossenen Jugendverbände fordern die uneingeschränkte Solidarität mit flüchtenden Menschen, insbesondere mit Kindern und Jugendlichen, die nach Europa und Deutschland kommen. Niemand flüchtet freiwillig aus seiner Heimat. Fluchtgeschichten sind oftmals Geschichten von Krieg und Gewalt, Diskriminierung, Verfolgung oder Unterdrückung sowie schweren Menschenrechtsverletzungen. Es sind aber auch Geschichten von wirtschaftlichem oder sozialem Elend, Krankheiten – vor allem aber von Verzweiflung und oftmals auch dem Gefühl, nicht willkommen zu sein.</p>
<p> </p>
<p><strong>Sichere Wege in Europäische Union eröffnen!</strong></p>
<p> </p>
<p>Die Abschottung der EU-Außengrenzen zwingt Flüchtende zu lebensgefährlichen Überfahrten in unsicheren Booten über das Mittelmehr. Selbst dort ist nicht die Seenotrettung, sondern die Abwehr von Geflüchteten primäres Ziel der EU und ihrer Mitgliedsstaaten. Der Status Quo fördert den gefährlichen, oft tödlichen Menschenschmuggel und steht diametral den Grundwerten der Europäischen Union entgegen.</p>
<p> </p>
<p>Wir als junge Europäer_innen wollen keine "Festung Europa", sondern ein offenes, tolerantes Europa, für das die Aufnahme von flüchtenden Menschen in Not selbstverständlich ist. Die Europäische Union muss Flüchtenden geregelte und sichere Passagen nach Europa ermöglichen. Ein solidarischer Neuanfang in der europäischen Asyl- und Flüchtlingspolitik ist unabdingbar!</p>
<p>Insbesondere fordern wir:</p>
<ul>
<li>Schutzsuchende müssen die EU auf legalem und sicherem Wege erreichen können. Wir fordern die EU-Staaten auf, endlich ein einheitliches Verfahren einzurichten, das allen Geflüchteten faire Chancen und eine Grundversorgung nach gemeinsam definierten Standards bietet. Dies umfasst auch die Erteilung humanitärer EU-Visa!</li>
	<li>Die völkerrechtswidrigen „Push-Backs“ an den südlichen EU-Landgrenzen (v.a. Bulgarien, Griechenland, Ceuta und Mellila) müssen beendet werden!</li>
</ul>
<p> </p>
<ul>
<li>
	<p>Bei der Einrichtung sogenannter Hotspots an den Außengrenzen der EU müssen eine menschenwürdige Unterbringung, Verpflegung und Versorgung der Geflüchteten unter Beachtung europäischer und internationaler Standards gewährleistet sein!</p>
	</li>
</ul>
<p> </p>
<ul>
<li>Im Irak, Türkei und in Jordanien warten Frauen und Kinder mitunter jahrelang unter lebensbedrohlichen und prekären Umständen auf den ihnen zustehenden Familiennachzug. Dieser muss schnell und unkompliziert ermöglicht werden. Dazu muss das Personal der Botschaften der EU-Mitgliedsstaaten in diesen Ländern deutlich aufgestockt werden!</li>
</ul>
<p> </p>
<p> </p>
<p> </p>
<p><strong>Solidarische und faire Verteilung Geflüchteter gewährleisten!</strong></p>
<p>Durch das Dublin-Verfahrenwerden die südlichen EU-Staaten (insbesondere Malta, Italien, Spanien und Griechenland) seit Jahren überproportional stark belastet. Die Abschaffung der Dublin-Regelungen und ein solidarischer Neuanfang in der europäischen Asyl- und Flüchtlingspolitik gemäß Artikel 78 Absatz 2 AEUV sindunabdingbar!</p>
<p>Alle Mitgliedsstaaten sind gleichermaßen in der Pflicht, Flüchtenden ohne Kategorisierung nach Herkunft oder Religion Schutz zu gewähren. Wir fordern, zügig und dauerhaft einen verbindlichen und gerechten EU-Verteilungsschlüssel zu etablieren, der die Bevölkerungszahl und die Wirtschaftskraft des jeweiligen Mitgliedsstaats berücksichtigt. Auch individuelle Faktoren, wie die Familienzusammenführung und Sprachkenntnisse, müssen Berücksichtigung finden. Im Sinne der Genfer Flüchtlingskonvention fordern wir perspektivisch, Geflüchteten die Wahl des für ihr Asylverfahren zuständigen Staates selbst zu überlassen. Dies könnte beispielsweise dadurch realisiert werden, dass die finanzielle Verantwortung durch einen gesamteuropäischen Fonds getragen wird.</p>
<p> </p>
<p> </p>
<p> </p>
<p> </p>
<p><strong>Keine willkürliche Deklaration „sicherer Herkunftsstaaten“!</strong></p>
<p> </p>
<p>Wir kritisieren, dass unter dem Vorwand hoher Zahlen Geflüchteter immer mehr Staaten zu sog. „sicheren Herkunftsstaaten“ erklärt werden. Nach wie vor werden in Europa Minderheiten unterdrückt, verfolgt und nur unzureichend geschützt. Jede*r Asylsuchende hat einen Rechtanspruch auf eine sorgfältige Einzelfallprüfung!</p>
<p>Insbesondere wird die Situation für LGBT*s in den Herkunftsländern häufig nicht ausreichend berücksichtigt, die durch die Genfer Flüchtlingsrechtskonvention nicht eindeutig geschützt und deshalb häufig übergangen werden. Länder, in welchen LGBT*s rechtlich verfolgt oder vom Staat nicht vor der Willkür der Bevölkerung geschützt werden, dürfen nicht als sichere Herkunftsländer gelten!</p>
<p> </p>
<p><strong>Für eine europäische Willkommenskultur!</strong></p>
<p>Da die Probleme, die zur derzeitigen Massenflucht führen, kurzfristig nicht zu lösen sind, ist es unerlässlich, den geflüchteten Menschen die Integration in die europäische Gesellschaft zu ermöglichen. Einerseits, um vorübergehende Perspektiven für sie zu entwickeln, aber auch um im Bedarfsfall die vollständige und dauerhafte Aufnahme zu erleichtern. Gerade vor der Fragestellung des Demographischen Wandels und des Bevölkerungsrückgangs in Europa ist die Integration von Geflüchteten eine große Chance.</p>
<p>Für eine erfolgreiche Integration sind neben einer europäischen Willkommenskultur die non-formalen und formalen Erasmus+ Bildungsprogramme der EU wichtige Instrumente, die es auszubauen gilt. Die inhaltliche Schwerpunktsetzung dieser Programme muss sich jedoch flexibel an den gewandelten Anforderungen orientieren und auf Integration durch Begegnung und Verständigung setzen. Auch der Abbau von Vorurteilen, der Kampf gegen den Rassismus, gegen antidemokratische Strömungen und den demagogischen Rechtspopulismus in Europa, müssen vorrangige Bildungsziele werden. Um den alten und neuen Aufgaben gerecht zu werden, ist eine höhere Investition in die EU-Bildungsprogramme dringend nötig.</p>
<p> </p>
<p><strong>Fluchtursachen bekämpfen!</strong></p>
<p>Verantwortung zu übernehmen, heißt aber auch eine Politik und Wirtschaft zu betreiben, die nicht zu Lasten der Länder geht, aus denen gerade viele Menschen fliehen. Der Schlüssel zur Lösung der Fluchtkatastrophe liegt deshalb nicht nur in der Europäischen Union, sondern auch in den Herkunftsländern der Geflüchteten. Waffenexporte in diese Regionen sind entschieden abzulehnen. Solange es dort weiterhin keine menschenwürdigen Lebensperspektiven gibt, wird der Flüchtlingsstrom nicht abreißen. Wir fordern die Europäische Union und die Bundesregierung dazu auf, diese Länder aktiv bei demokratischen Reformen und einer nachhaltigen Entwicklung zu unterstützen.</p>
<p> </p>
<p> </p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(0);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_CLASSES);

        $this->assertCount(4, $diffGroups);
    }

    #[Incomplete('TODO')]
    public function testEmptyDeletedSpaceAtEnd(): void
    {
        $strPre  = '<p>Wir sind froh und dankbar über alle, die in der Krise anpacken statt bloß zu lamentieren. Das vielleicht hervorstechendste Moment der letzten Wochen und Monate ist die schier unendliche Hilfsbereitschaft und der Wille zu einem solidarischen Engagement für Flüchtlinge – und zwar quer durch alle Gesellschaftsschichten, in Stadt und Land. Wer dagegen in dieser Situation zündelt und Stimmung gegen Flüchtlinge schürt, handelt unverantwortlich. Hier wissen wir die vielen Bürger*innen in diesem Land auf unserer Seite, die sich dem rechten Mob entgegenstellen, der die Not von Schutzsuchenden für Hass und rechtsextreme Propaganda missbraucht.</p>';
        $strPost = '<p>Wir sind froh und dankbar über alle, die in der Krise anpacken statt bloß zu lamentieren. Das vielleicht hervorstechendste Moment der letzten Wochen und Monate ist die schier unendliche Hilfsbereitschaft und der Wille zu einem solidarischen Engagement für Flüchtlinge – und zwar quer durch alle Gesellschaftsschichten, in Stadt und Land. Wer dagegen in dieser Situation zündelt und Stimmung gegen Flüchtlinge schürt, handelt unverantwortlich.</p>
<p>Hier wissen wir die vielen Bürger*innen in diesem Land auf unserer Seite, die sich konsequent rechtsextremen Tendenzen entgegenstellen, welche die Not von Schutzsuchenden für Hass und populistische Propaganda missbrauchen.</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_INLINE);

        $text   = TextSimple::formatDiffGroup($diffGroups);
        $expect = '<h4 class="lineSummary">Von Zeile 6 bis 9:</h4><div><p>zündelt und Stimmung gegen Flüchtlinge schürt, handelt unverantwortlich.<br><ins class="space">[Zeilenumbruch]</ins><ins><br></ins>Hier wissen wir die vielen Bürger*innen in diesem Land auf unserer Seite, die sich <del>dem rechten </del><del>Mob</del><ins>konsequent rechtsextremen Tendenzen</ins> entgegenstellen, <del>der</del><ins>welche</ins> die Not von Schutzsuchenden für Hass und <del>rechtsextreme</del><ins>populistische</ins> Propaganda missbrauch<del>t</del><ins>en</ins>.</p></div>';
        $this->assertEquals($expect, $text);
    }

    public function testInlineFormatting(): void
    {
        $strPre  = '<p>Test 123</p>';
        $strPost = '<p>Test</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_INLINE);

        $this->assertCount(1, $diffGroups);

        $text   = TextSimple::formatDiffGroup($diffGroups);
        $expect = '<h4 class="lineSummary">In Zeile 1 löschen:</h4><div><p>Test<del style="color:#FF0000;text-decoration:line-through;"> 123</del></p></div>';
        $this->assertEquals($expect, $text);
    }

    public function testWhitespaceDeleted(): void
    {
        $diffGroups = [
            self::getAffectedLinesBlock(13, 14, '<ul><li value="1">###LINENUMBER###sich die Eltern flexibel untereinander aufteilen (8+8+8). Auch ###LINENUMBER###Alleinerziehende haben einen Anspruch auf 24 Monate FamilienZeitPlus<del aria-label="Streichen: „”"> </del></li></ul>')
        ];
        $text       = TextSimple::formatDiffGroup($diffGroups);
        $expect     = '<h4 class="lineSummary">Von Zeile 13 bis 14 löschen:</h4><div><ul><li value="1">sich die Eltern flexibel untereinander aufteilen (8+8+8). Auch Alleinerziehende haben einen Anspruch auf 24 Monate FamilienZeitPlus<del class="space" aria-label="Streichen: „Leerzeichen”">[Leerzeichen]</del></li></ul></div>';
        $this->assertEquals($expect, $text);
    }

    public function testLineBreaksWithinParagraphs(): void
    {
        // 'Line breaks within paragraphs'
        $orig = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf maßlose Deregulierung; eine Politik der sozialen Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; eine Politik, die auch unpopuläre Strukturreformen angeht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>Die Kaputtsparpolitik ist gescheitert<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        $new = '<p>Um die ökonomischen, sozialen und ökologischen Probleme in Angriff zu nehmen, müssen wir umsteuern. Dazu brauchen wir einen Green New Deal für Europa, der eine umfassende Antwort auf die Krisen der Gegenwart gibt. Er enthält mehrere Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische Innovationen setzt statt auf Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen Spaltung unserer Gesellschaften; ein Wirtschaftsmodell, das auch unbequeme Strukturreformen mit einbezieht, wenn diese zu nachhaltigem Wachstum und mehr Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde Rechtsstaatlichkeit angehen und eine Politik, die die Glaubwürdigkeit in Europa, dass Schulden auch bedient werden, untermauert.</p>
<p>Die Kaputtsparpolitik ist gescheitert<br>
Die Strategie zur Krisenbewältigung der letzten fünf Jahre hat zwar ein wichtiges Ziel erreicht: Der Euro, als entscheidendes Element der europäischen Integration und des europäischen Zusammenhalts, konnte bislang gerettet werden. Dafür hat Europa neue Instrumente und Mechanismen geschaffen, wie den Euro-Rettungsschirm mit dem Europäischen Stabilitätsmechanismus (ESM) oder die Bankenunion. Aber diese Instrumente allein werden die tiefgreifenden Probleme nicht lösen - weder politisch noch wirtschaftlich.</p>';

        $expect = [self::getAffectedLinesBlock(
            4,
            9,
            '<p>###LINENUMBER###Komponenten: eine nachhaltige Investitionsstrategie, die auf ökologische ' .
            '###LINENUMBER###Innovationen setzt statt auf <del>maßlose Deregulierung; eine Politik der sozialen</del><ins>Deregulierung und blindes Vertrauen in die Heilkräfte des Marktes; einen Weg zu mehr sozialer</ins> ' .
            '###LINENUMBER###Gerechtigkeit statt der Gleichgültigkeit gegenüber der ständig schärferen ' .
            '###LINENUMBER###Spaltung unserer Gesellschaften; <del>eine Politik, die</del><ins>ein Wirtschaftsmodell, das</ins> auch <del>unpopuläre</del><ins>unbequeme</ins> ' .
            '###LINENUMBER###Strukturreformen <del>angeht</del><ins>mit einbezieht</ins>, wenn diese zu nachhaltigem Wachstum und mehr ' .
            '###LINENUMBER###Gerechtigkeit beitragen; ein Politik die Probleme wie Korruption und mangelnde </p>',
        )];

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

    public function testGroupingChangedBlocks1(): void
    {
        $blocks  = [
            '<p><del>###LINENUMBER###Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy ###LINENUMBER###eirmod tempor invidunt ut labore et dolore magna aliquyam erat</del><ins>Zombie ipsum reversus ab viral inferno, nam rick grimes malum cerebro. De carne lumbering animata corpora quaeritis. Summus brains sit​​, morbo vel maleficia?</ins></p>',
            '<p><del>###LINENUMBER###Lorem ipsum dolor sit amet</del><ins>Bavaria ipsum</ins></p><p class="inserted">Another inserted paragraph</p>',
            '<ul class="deleted"><li>###LINENUMBER###Old list</li></ul><ol class="inserted"><li>New list</li></ol>'
        ];
        $grouped = AmendmentSectionFormatter::groupConsecutiveChangeBlocks($blocks);
        $this->assertEquals([
            '<p class="deleted">###LINENUMBER###Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy ###LINENUMBER###eirmod tempor invidunt ut labore et dolore magna aliquyam erat</p>',
            '<p class="deleted">###LINENUMBER###Lorem ipsum dolor sit amet</p>',
            '<ul class="deleted"><li>###LINENUMBER###Old list</li></ul>',
            '<p class="inserted">Zombie ipsum reversus ab viral inferno, nam rick grimes malum cerebro. De carne lumbering animata corpora quaeritis. Summus brains sit​​, morbo vel maleficia?</p>',
            '<p class="inserted">Bavaria ipsum</p>',
            '<p class="inserted">Another inserted paragraph</p>',
            '<ol class="inserted"><li>New list</li></ol>',
        ], $grouped);
    }

    public function testSeveralUnchangedLinesAtBeginning1(): void
    {
        $strPre  = '<p>Über den Körper selbst zu bestimmen, ist nicht leicht, wenn alle eine Meinung dazu haben. Wir setzen uns für das Selbstbestimmungsrecht von Frauen und Mädchen über ihren Körper ein. Daher verteidigen wir die Straffreiheit von Schwangerschaftsabbrüchen gegen die Angriffe von rechts. Frauen in Notlagen brauchen Unterstützung und Hilfe, keine Bevormundung und keine Strafe.</p>';
        $strPost = '<p>Über den Körper selbst zu bestimmen, ist nicht leicht, wenn alle eine Meinung dazu haben. Wir setzen uns für das Selbstbestimmungsrecht von Frauen und Mädchen über ihren Körper ein. Bei ungewollter Schwangerschaft brauchen Frauen wohnortnahe Unterstützung und Hilfe, keine Bevormundung und keine Strafe. Erst recht brauchen sie keinen Rückschritt bei bereits erkämpften Rechten und keine Einschränkungen erreichter Freiheiten.</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(1);

        $diffGroups = $formatter->getDiffGroupsWithNumbers(92, DiffRenderer::FORMATTING_CLASSES, 0);
        $this->assertCount(1, $diffGroups);
        $this->assertEquals(3, $diffGroups[0]->lineFrom);
        $this->assertEquals(5, $diffGroups[0]->lineTo);

        $diffGroups = $formatter->getDiffGroupsWithNumbers(92, DiffRenderer::FORMATTING_CLASSES, 1);
        $this->assertCount(1, $diffGroups);
        $this->assertEquals(2, $diffGroups[0]->lineFrom);
        $this->assertEquals(5, $diffGroups[0]->lineTo);
    }

    public function testGroupingChangedBlocks2(): void
    {
        // To cases in which the grouping has no effect: nested INS/DEL-Tags, and Paragraphs that are nur purely inserted/deleted
        $blocks  = [
            '<p><del>###LINENUMBER###Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy ###LINENUMBER###eirmod tempor invidunt <ins>ut</ins> labore et dolore magna aliquyam erat</del><ins>Zombie ipsum reversus ab viral inferno, nam rick grimes malum cerebro. De carne lumbering animata corpora quaeritis. Summus brains sit​​, morbo vel maleficia?</ins></p>',
            '<p><del>###LINENUMBER###Lorem ipsum dolor sit amet</del><ins>Bavaria ipsum</ins>Test</p><p class="inserted">Another inserted paragraph</p>',
        ];
        $grouped = AmendmentSectionFormatter::groupConsecutiveChangeBlocks($blocks);
        $this->assertEquals($blocks, $grouped);
    }

    public function testSeveralUnchangedLinesAtBeginning2(): void
    {
        $strPre  = '<p>Körper selbst zu bestimmen, ist nicht leicht, wenn alle eine Meinung dazu haben. Wir setzen uns für das Selbstbestimmungsrecht von Frauen und Mädchen über ihren Körper ein. Daher verteidigen wir die Straffreiheit von Schwangerschaftsabbrüchen gegen die Angriffe von rechts. Frauen in Notlagen brauchen Unterstützung und Hilfe, keine Bevormundung und keine Strafe.</p>';
        $strPost = '<p>Körper selbst zu bestimmen, ist nicht leicht, wenn alle eine Meinung dazu haben. Wir setzen uns für das Selbstbestimmungsrecht von Frauen und Mädchen über ihren Körper ein. Bei ungewollter Schwangerschaft brauchen Frauen wohnortnahe Unterstützung und Hilfe, keine Bevormundung und keine Strafe. Erst recht brauchen sie keinen Rückschritt bei bereits erkämpften Rechten und keine Einschränkungen erreichter Freiheiten.</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($strPre);
        $formatter->setTextNew($strPost);
        $formatter->setFirstLineNo(1);
        $diffGroups = $formatter->getDiffGroupsWithNumbers(92, DiffRenderer::FORMATTING_CLASSES);

        $this->assertCount(1, $diffGroups);
        $this->assertEquals(1, $diffGroups[0]->lineFrom);
        $this->assertEquals(5, $diffGroups[0]->lineTo);
    }
}
