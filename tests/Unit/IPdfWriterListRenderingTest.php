<?php

namespace Tests\Unit;

use app\views\pdfLayouts\IPdfWriter;
use Tests\Support\Helper\TestBase;

class IPdfWriterListRenderingTest extends TestBase
{
    /**
     * Renders the HTML through IPdfWriter and returns the uncompressed PDF content streams.
     */
    private function renderToContentStream(string $html): string
    {
        $pdf = new IPdfWriter('P', 'mm', 'A4');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->AddPage();
        $pdf->writeHTMLCell(150, 0, 40, 30, $html, 0, 1, false, true, '', true);
        $out = $pdf->Output('', 'S');

        $content = '';
        preg_match_all('/stream\r?\n(.*?)endstream/s', $out, $streams);
        foreach ($streams[1] as $stream) {
            $inflated = @gzuncompress($stream);
            if ($inflated !== false) {
                $content .= $inflated . "\n";
            }
        }

        return $content;
    }

    /**
     * Returns all text strings drawn onto the page, in drawing order.
     * List markers that are rendered separately, left of the text block, appear as their own entries.
     *
     * @return string[]
     */
    private function getRenderedStrings(string $html): array
    {
        preg_match_all('/\((?:[^()\\\\]|\\\\.)*\)/', $this->renderToContentStream($html), $matches);

        return array_map(fn (string $str): string => stripcslashes(substr($str, 1, -1)), $matches[0]);
    }

    /**
     * Returns the [x, y] position for each drawn text string.
     *
     * @return array<string, array{float, float}>
     */
    private function getRenderedStringPositions(string $html): array
    {
        $content = $this->renderToContentStream($html);
        preg_match_all('/([0-9.]+) ([0-9.]+) Td \(((?:[^()\\\\]|\\\\.)*)\) Tj/s', $content, $ops, PREG_SET_ORDER);

        $positions = [];
        foreach ($ops as $op) {
            $positions[stripcslashes($op[3])] = [floatval($op[1]), floatval($op[2])];
        }

        return $positions;
    }

    public function testNestedListsWithExplicitValues(): void
    {
        // Same test case as HTML2TexTest::testNestedLists:
        // numeric / single-letter values set the counter, other values are shown verbatim
        // without affecting the counter
        $html = '<ol start="4"><li>Test 2'
            . '<ol class="decimalCircle"><li>Test a</li>'
            . '<li value="g">Test c</li>'
            . '<li value="i/">Test d</li>'
            . '<li>Test e</li></ol></li>'
            . '<li>Test 5</li></ol>';

        $this->assertSame([
            '4.', 'Test 2',
            '(1)', 'Test a',
            '(g)', 'Test c',
            '(i/)', 'Test d',
            '(9)', 'Test e',
            '5.', 'Test 5',
        ], $this->getRenderedStrings($html));
    }

    public function testClassBasedListStyles(): void
    {
        $html = '<ol class="upperAlpha"><li>Alpha</li><li>Beta</li></ol>';
        $this->assertSame(['A.', 'Alpha', 'B.', 'Beta'], $this->getRenderedStrings($html));

        // lowerAlpha continues into two-letter values after "z"
        $html = '<ol class="lowerAlpha" start="26"><li>Zulu</li><li>Alpha-Alpha</li></ol>';
        $this->assertSame(['z.', 'Zulu', 'aa.', 'Alpha-Alpha'], $this->getRenderedStrings($html));

        $html = '<ol class="decimalCircle" start="2"><li>Two</li><li>Three</li></ol>';
        $this->assertSame(['(2)', 'Two', '(3)', 'Three'], $this->getRenderedStrings($html));
    }

    public function testNumericValueSetsCounter(): void
    {
        $html = '<ol><li>one</li><li value="7">seven</li><li>eight</li></ol>';
        $this->assertSame(['1.', 'one', '7.', 'seven', '8.', 'eight'], $this->getRenderedStrings($html));
    }

    public function testMarkerIsRenderedLeftOfTextBlock(): void
    {
        $positions = $this->getRenderedStringPositions('<ol class="decimalCircle" start="41"><li>Test Item</li></ol>');

        $this->assertArrayHasKey('(41)', $positions);
        $this->assertArrayHasKey('Test Item', $positions);
        // left of the text ...
        $this->assertLessThan($positions['Test Item'][0], $positions['(41)'][0]);
        // ... on the same line
        $this->assertEqualsWithDelta($positions['Test Item'][1], $positions['(41)'][1], 0.1);
    }

    public function testListItemsStartingWithBlockElementShareTheMarkerLine(): void
    {
        // A <p> (or other non-list block element) as the first child of a <li> must start
        // on the same line as the list marker, for every list item and nesting level
        $html = '<ol class="decimalCircle"><li><p>one</p></li>'
            . '<li value="1b"><p>one-b</p><ol class="lowerAlpha"><li><p>sub a</p></li><li><p>sub b</p></li></ol></li>'
            . '<li>plain</li></ol>';

        $positions = $this->getRenderedStringPositions($html);
        foreach ([['(1)', 'one'], ['(1b)', 'one-b'], ['a.', 'sub a'], ['b.', 'sub b'], ['(3)', 'plain']] as $pair) {
            $this->assertArrayHasKey($pair[0], $positions);
            $this->assertArrayHasKey($pair[1], $positions);
            $this->assertEqualsWithDelta(
                $positions[$pair[1]][1],
                $positions[$pair[0]][1],
                0.1,
                'Marker ' . $pair[0] . ' is not on the same line as its text'
            );
        }
    }

    /**
     * Renders with the setHtmlVSpace() configuration used by all PDF layouts and returns
     * the y position of each drawn string.
     *
     * @return array<string, float>
     */
    private function getRenderedStringYPositionsWithVSpace(string $html): array
    {
        $pdf = new IPdfWriter('P', 'mm', 'A4');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->setCellHeightRatio(1.5);
        $pdf->setHtmlVSpace([
            'ul' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'ol' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'li' => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
            'p'  => [['h' => 0, 'n' => 0], ['h' => 0, 'n' => 0]],
        ]);
        $pdf->AddPage();
        $pdf->writeHTMLCell(150, 0, 40, 30, $html, 0, 1, false, true, '', true);
        $out = $pdf->Output('', 'S');

        $content = '';
        preg_match_all('/stream\r?\n(.*?)endstream/s', $out, $streams);
        foreach ($streams[1] as $stream) {
            $inflated = @gzuncompress($stream);
            if ($inflated !== false) {
                $content .= $inflated . "\n";
            }
        }
        preg_match_all('/([0-9.]+) ([0-9.]+) Td \(((?:[^()\\\\]|\\\\.)*)\) Tj/s', $content, $ops, PREG_SET_ORDER);
        $positions = [];
        foreach ($ops as $op) {
            $positions[stripcslashes($op[3])] = floatval($op[2]);
        }

        return $positions;
    }

    public function testSetHtmlVSpaceDisablesDefaultBlockMargins(): void
    {
        // With setHtmlVSpace() configured (as done by all PDF layouts), an <ol> following a <br>
        // must start on the very next line, like in TCPDF 6 - crucial for the line numbers
        // printed next to motion texts. (10pt font, cell height ratio 1.5 => 15pt per line)
        $positions = $this->getRenderedStringYPositionsWithVSpace('first line<br><ol start="2"><li>list line</li></ol>');

        $this->assertArrayHasKey('first line', $positions);
        $this->assertArrayHasKey('list line', $positions);
        $this->assertEqualsWithDelta(15.0, $positions['first line'] - $positions['list line'], 0.1);
    }

    public function testLeadingBrIsSwallowed(): void
    {
        // Like TCPDF 6, a <br> at the very beginning of a cell must not advance the line:
        // printMotionSection() relies on this for the additional empty slots it inserts
        // into the line number column
        $plain = $this->getRenderedStringYPositionsWithVSpace('erste Zeile<br>zweite Zeile');
        $leadingBr = $this->getRenderedStringYPositionsWithVSpace('<br>erste Zeile<br>zweite Zeile');

        $this->assertEqualsWithDelta($plain['erste Zeile'], $leadingBr['erste Zeile'], 0.1);
        // ... but only the first one
        $doubleBr = $this->getRenderedStringYPositionsWithVSpace('<br><br>erste Zeile');
        $this->assertEqualsWithDelta(15.0, $plain['erste Zeile'] - $doubleBr['erste Zeile'], 0.1);
    }

    public function testListItemSpacingMarkup(): void
    {
        // printMotionSection() separates list items with </li><br><li>-markup.
        // Matching TCPDF 6: for plain list items the <br> advances one line,
        // for <p>-wrapped items the closing </p> adds another one (empty line between the items)
        $positions = $this->getRenderedStringYPositionsWithVSpace('<ol start="6"><li>Punkt sechs</li><br><li>Punkt sieben</li></ol>');
        $this->assertEqualsWithDelta(15.0, $positions['Punkt sechs'] - $positions['Punkt sieben'], 0.1);

        $positions = $this->getRenderedStringYPositionsWithVSpace('<ol start="6"><li><p>Punkt sechs</p></li><br><li><p>Punkt sieben</p></li></ol>');
        $this->assertEqualsWithDelta(2 * 15.0, $positions['Punkt sechs'] - $positions['Punkt sieben'], 0.1);
    }

    public function testNestedListStartsWithOneEmptyLine(): void
    {
        // The junction "...</p><br><ol..." (nested list after a <p>-wrapped text, joined by
        // printMotionSection) must result in exactly one empty line, like in TCPDF 6
        $positions = $this->getRenderedStringYPositionsWithVSpace(
            '<ol class="decimalCircle" start="2"><li value="1b"><p>Haupttext</p><br><ol class="lowerAlpha"><li><p>Untertext</p></li></ol></li></ol>'
        );

        $this->assertArrayHasKey('Haupttext', $positions);
        $this->assertArrayHasKey('Untertext', $positions);
        $this->assertEqualsWithDelta(2 * 15.0, $positions['Haupttext'] - $positions['Untertext'], 0.1);
        // markers share the line with their text
        $this->assertEqualsWithDelta($positions['Haupttext'], $positions['(1b)'], 0.1);
        $this->assertEqualsWithDelta($positions['Untertext'], $positions['a.'], 0.1);
    }
}
