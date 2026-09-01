<?php

namespace Tests\Unit;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DiffRenderer;
use app\components\diff\TwoLayerDiff;
use Tests\Support\Helper\TestBase;

class TwoLayerDiffTest extends TestBase
{
    /**
     * @param string[] $original
     * @param string[] $parent
     * @param string[] $child
     * @return string[]
     */
    private function combine(array $original, array $parent, array $child): array
    {
        $twoLayer = new TwoLayerDiff();
        $combined = $twoLayer->computeParagraphs($original, $parent, $child);
        $this->assertNotNull($combined);

        return $combined;
    }

    /**
     * @param string[] $original
     * @param string[] $parent
     * @param string[] $child
     * @return string[]
     */
    private function combineAndRender(array $original, array $parent, array $child): array
    {
        $twoLayer = new TwoLayerDiff();
        $rendered = $twoLayer->computeAndRenderParagraphs($original, $parent, $child, DiffRenderer::FORMATTING_CLASSES);
        $this->assertNotNull($rendered);

        return $rendered;
    }

    public function testUnchangedByChild(): void
    {
        // The child amendment proposes exactly what the parent amendment proposes
        // => everything is part of the outer layer, nothing of the inner one
        $rendered = $this->combineAndRender(
            ['<p>Test 123 aber das hier nicht</p>'],
            ['<p>Test 1234567 aber das hier</p>'],
            ['<p>Test 1234567 aber das hier</p>']
        );

        $this->assertCount(1, $rendered);
        $this->assertSame(
            '<p>Test <del class="outer">123</del><ins class="outer">1234567</ins>' .
            ' aber das hier<del class="outer"> nicht</del></p>',
            $rendered[0]
        );
    }

    public function testComplexScenario(): void
    {
        $rendered = $this->combineAndRender(
            ['<p>(3) Amtsträger*innen des Verbands im Regionalrat und in der Bezirksversammlung sowie Inhaber*innen von Leitungsfunktionen auf Verbandsebene leisten neben ihren satzungsgemäßen Mitgliedsbeiträgen (Abschnitt 4 Absatz 2) Funktionsträger*innenbeiträge an den Hauptverband. Die Höhe der Funktionsträger*innenbeiträge wird von der Mitgliederversammlung bestimmt.</p>'],
            ['<p>(3) Amtsträger*innen des Verbands im Regionalrat und in der Bezirksversammlung sowie Inhaber*innen von Leitungsfunktionen auf Verbandsebene (einschließlich Referatsleitungen sowie hauptamtliche und ehrenamtliche Beauftragte) und Mitglieder des Beirats leisten neben ihren satzungsgemäßen Mitgliedsbeiträgen (Abschnitt 4 Absatz 2) Zusatzbeiträge.<br>
Die Zusatzbeiträge sind für den Zeitraum der Ausübung des Amtes oder der Funktion abzuführen.<br>
Die Zusatzbeiträge werden auf alle Bezüge, also Aufwandsentschädigungen, Sitzungsgelder und Vergütungen aus Amt oder Funktion erhoben. Die Höhe der Zusatzbeiträge muss mindestens 15% und darf höchstens 25% der entsprechenden Gesamteinnahmen aus Amt und/oder Funktion betragen. Eine unterschiedliche Belastung aufgrund der jeweiligen Funktionen und Ämter ist möglich.<br>
Die Einzelheiten, wie die Höhe des Beitrags und das Erhebungsverfahren, werden durch den Finanzausschuss in einer Zusatzbeitragsordnung konkretisiert, die veröffentlicht wird und durch Beschluss der Hauptversammlung geändert werden kann.<br>
Der an der jeweiligen Anspruchshöhe gemessene individuelle Erfüllungsgrad sowie der Name der Amts- und Funktionsträger*innen wird verbandsöffentlich zugänglich gemacht.</p>'],
            ['<p>(3) Amtsträger*innen des Verbands im Regionalrat und in der Bezirksversammlung sowie Inhaber*innen von Leitungsfunktionen auf Verbandsebene (einschließlich Referatsleitungen sowie hauptamtliche Beauftragte) und Mitglieder des Beirats leisten neben ihren satzungsgemäßen Mitgliedsbeiträgen (Abschnitt 4 Absatz 2) Zusatzbeiträge. Die Zusatzbeiträge sind für den Zeitraum der Ausübung des Amtes oder der Funktion abzuführen.<br>
Die Zusatzbeiträge werden auf Aufwandsentschädigungen und Vergütungen aus Amt oder Funktion erhoben. Die Höhe des Zusatzbeitrags beschließt die Hauptversammlung. Eine unterschiedliche Belastung aufgrund der jeweiligen Funktionen und Ämter ist möglich.<br>
Für Eltern und Härtefälle werden Sonderregelungen zur Reduzierung des festgelegten Zusatzbeitrags erlassen. Die Einzelheiten dazu und das Erhebungsverfahren werden durch den Finanzausschuss in einer Zusatzbeitragsordnung konkretisiert, die veröffentlicht wird und durch Beschluss der Hauptversammlung geändert werden kann.<br>
Der Finanzausschuss beteiligt Vertreter*innen der Mitglieder aller betroffenen Ebenen an seinen Beratungen zur Zusatzbeitragsordnung. Der an der jeweiligen Anspruchshöhe gemessene individuelle Erfüllungsgrad sowie der Name der Amts- und Funktionsträger*innen wird verbandsöffentlich zugänglich gemacht. Ebenso wird verbandsöffentlich zugänglich gemacht, inwiefern die Zusatzbeiträge für Kampagnen der jeweiligen Ebene verwendet werden.</p>']
        );
        // The paragraph is rewritten so heavily that the regular diff would not show a fine-grained diff
        // against the original anymore. The two-layered diff follows suit: one block for what the statute says,
        // one block for what the amended amendment proposes - and the changes of this amendment shown *within*
        // that block. Without that, single words that happen to occur in both texts ("den", "der", "wird")
        // would be reported as unchanged and tear the two blocks apart.
        $this->assertCount(1, $rendered);
        $this->assertSame(
            '<p>(3) Amtsträger*innen des Verbands im Regionalrat und in der Bezirksversammlung sowie ' .
            'Inhaber*innen von Leitungsfunktionen auf Verbandsebene ' .
            '<del class="outer">leisten neben ihren satzungsgemäßen Mitgliedsbeiträgen (Abschnitt 4 Absatz 2) ' .
            'Funktionsträger*innenbeiträge an den Hauptverband. Die Höhe der Funktionsträger*innenbeiträge wird ' .
            'von der Mitgliederversammlung bestimmt.</del>' .
            '<ins class="outer">(einschließlich Referatsleitungen sowie hauptamtliche ' .
            '<del>und ehrenamtliche </del>Beauftragte) und Mitglieder des Beirats leisten neben ihren ' .
            'satzungsgemäßen Mitgliedsbeiträgen (Abschnitt 4 Absatz 2) Zusatzbeiträge.<del><br></del><ins> </ins>' .
            'Die Zusatzbeiträge sind für den Zeitraum der Ausübung des Amtes oder der Funktion abzuführen.<br>' .
            'Die Zusatzbeiträge werden auf <del>alle Bezüge, also Aufwandsentschädigungen, Sitzungsgelder</del>' .
            '<ins>Aufwandsentschädigungen</ins> und Vergütungen aus Amt oder Funktion erhoben. Die Höhe ' .
            '<del>der Zusatzbeiträge muss mindestens 15% und darf höchstens 25% der entsprechenden ' .
            'Gesamteinnahmen aus Amt und/oder Funktion betragen</del>' .
            '<ins>des Zusatzbeitrags beschließt die Hauptversammlung</ins>. Eine unterschiedliche ' .
            'Belastung aufgrund der jeweiligen Funktionen und Ämter ist möglich.<br>' .
            '<del>Die Einzelheiten, wie die Höhe</del>' .
            '<ins>Für Eltern und Härtefälle werden Sonderregelungen zur Reduzierung</ins> des ' .
            '<del>Beitrags</del><ins>festgelegten Zusatzbeitrags erlassen. Die Einzelheiten dazu</ins>' .
            ' und das Erhebungsverfahren<del>,</del> werden durch den Finanzausschuss in einer ' .
            'Zusatzbeitragsordnung konkretisiert, die veröffentlicht wird und durch Beschluss der ' .
            'Hauptversammlung geändert werden kann.<br>' .
            '<ins>Der Finanzausschuss beteiligt Vertreter*innen der Mitglieder aller betroffenen Ebenen an ' .
            'seinen Beratungen zur Zusatzbeitragsordnung. </ins>' .
            'Der an der jeweiligen Anspruchshöhe gemessene individuelle Erfüllungsgrad sowie der Name der Amts- ' .
            'und Funktionsträger*innen wird verbandsöffentlich zugänglich gemacht' .
            '<ins>. Ebenso wird verbandsöffentlich zugänglich gemacht, inwiefern die Zusatzbeiträge für ' .
            'Kampagnen der jeweiligen Ebene verwendet werden</ins>.</ins></p>',
            $rendered[0]
        );
    }

    public function testChangedInsertionAndUndeletion(): void
    {
        // The example from the feature request:
        // - the parent replaces "123" by "1234567", the child by "1234568"
        // - the parent deletes "nicht", the child only deletes "nich" and keeps the "t"
        $combined = $this->combine(
            ['<p>Test 123 aber das hier nicht</p>'],
            ['<p>Test 1234567 aber das hier </p>'],
            ['<p>Test 1234568 aber das hier t</p>']
        );

        $this->assertSame(
            '<p>Test ' .
            DiffRenderer::OUTER_DEL_START . '123' . DiffRenderer::OUTER_DEL_END .
            DiffRenderer::OUTER_INS_START . '123456' .
            DiffRenderer::DEL_START . '7' . DiffRenderer::DEL_END .
            DiffRenderer::INS_START . '8' . DiffRenderer::INS_END .
            DiffRenderer::OUTER_INS_END .
            ' aber das hier ' .
            DiffRenderer::OUTER_DEL_START . 'nicht' . DiffRenderer::OUTER_DEL_END .
            DiffRenderer::INS_START . 't' . DiffRenderer::INS_END .
            '</p>',
            $combined[0]
        );

        $rendered = $this->combineAndRender(
            ['<p>Test 123 aber das hier nicht</p>'],
            ['<p>Test 1234567 aber das hier </p>'],
            ['<p>Test 1234568 aber das hier t</p>']
        );
        $this->assertSame(
            '<p>Test <del class="outer">123</del><ins class="outer">123456<del>7</del><ins>8</ins></ins>' .
            ' aber das hier <del class="outer">nicht</del><ins>t</ins></p>',
            $rendered[0]
        );
    }

    public function testChildDropsInsertionOfParent(): void
    {
        // The parent inserts a word, the child does not want it
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet consetetur</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>']
        );

        $this->assertSame(
            '<p>Lorem ipsum dolor sit amet<ins class="outer"><del> consetetur</del></ins></p>',
            $rendered[0]
        );
    }

    public function testChildAddsOwnInsertion(): void
    {
        // The child inserts something at a place the parent did not touch => plain inner insertion
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet consetetur</p>'],
            ['<p>Lorem sed ipsum dolor sit amet consetetur</p>']
        );

        // "sed " is added by the child alone; the insertion of the parent is untouched by the child
        // and therefore stays purely on the outer layer
        $this->assertSame(
            '<p>Lorem <ins>sed </ins>ipsum dolor sit amet<ins class="outer"> consetetur</ins></p>',
            $rendered[0]
        );
    }

    public function testChildDeletesTextParentKept(): void
    {
        // Neither amendment touches "dolor", but the child deletes it => plain inner deletion
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet consetetur</p>'],
            ['<p>Lorem ipsum sit amet consetetur</p>']
        );

        $this->assertSame(
            '<p>Lorem ipsum <del>dolor </del>sit amet<ins class="outer"> consetetur</ins></p>',
            $rendered[0]
        );
    }

    public function testLineNumbersArePreserved(): void
    {
        $original = ['<p>###LINENUMBER###Lorem ipsum dolor sit amet, ###LINENUMBER###consetetur sadipscing elitr</p>'];
        $rendered = $this->combineAndRender(
            $original,
            ['<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam</p>'],
            ['<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy</p>']
        );

        // Both line number markers of the original survive, and in their original positions
        $this->assertSame(
            '<p>###LINENUMBER###Lorem ipsum dolor sit amet, ###LINENUMBER###consetetur sadipscing elitr' .
            '<ins class="outer">, sed diam<ins> nonumy</ins></ins></p>',
            $rendered[0]
        );
    }

    public function testWholeParagraphInsertedByParentAndChangedByChild(): void
    {
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing elitr</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing tempor</p>']
        );

        // The added paragraph belongs to the outer layer, the word the child replaced within it to the inner one
        $this->assertCount(1, $rendered);
        $this->assertSame(
            '<p>Lorem ipsum dolor sit amet</p>' .
            '<p class="insertedOuter">Consetetur sadipscing <del>elitr</del><ins>tempor</ins></p>',
            $rendered[0]
        );
    }

    /**
     * Paragraphs that only the amended amendment changed are context, not a change of this amendment,
     * and must therefore not show up in the "only changed paragraphs" view.
     */
    public function testOnlyChangedLinesIgnoresTheOuterLayer(): void
    {
        $original = '<p>Lorem ipsum dolor sit amet</p>' . "\n" .
                    '<p>Consetetur sadipscing elitr</p>' . "\n" .
                    '<p>Sed diam nonumy eirmod tempor</p>';
        $parent   = '<p>Lorem ipsum dolor sit amet, consetetur</p>' . "\n" .
                    '<p>Consetetur sadipscing elitr</p>' . "\n" .
                    '<p>Sed diam nonumy eirmod tempor</p>';
        $child    = '<p>Lorem ipsum dolor sit amet, consetetur</p>' . "\n" .
                    '<p>Consetetur sadipscing elitr</p>' . "\n" .
                    '<p>Sed diam nonumy eirmod incididunt</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($original);
        $formatter->setTextParent($parent);
        $formatter->setTextNew($child);
        $formatter->setFirstLineNo(1);

        $groups = $formatter->getTwoLayerDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_CLASSES, 0);
        $this->assertNotNull($groups);

        // Only the third paragraph shows up: the first one is changed by the amended amendment alone,
        // which is context rather than a change of this amendment
        $this->assertCount(1, $groups);
        $this->assertSame(3, $groups[0]->lineFrom);
        $this->assertSame(3, $groups[0]->lineTo);
        $this->assertSame(
            '<p>###LINENUMBER###Sed diam nonumy eirmod <del>tempor</del><ins>incididunt</ins></p>',
            $groups[0]->text
        );
    }

    public function testWholeParagraphDeletedByParentAndRestoredByChild(): void
    {
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing elitr</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing elitr</p>']
        );

        // Deleted by the parent, un-deleted by the child
        $this->assertCount(2, $rendered);
        $this->assertSame('<p>Lorem ipsum dolor sit amet</p>', $rendered[0]);
        $this->assertSame('<p class="deletedOuter inserted">Consetetur sadipscing elitr</p>', $rendered[1]);
    }
}
