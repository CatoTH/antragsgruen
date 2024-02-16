<?php

namespace Tests\Unit;

use app\components\HTMLTools;
use app\models\SectionedParagraph;
use Tests\Support\Helper\TestBase;

class HTMLSectioningTest extends TestBase
{
    public function testOlsWithStart(): void
    {
        $orig = '<ol class="lowerAlpha" start="3"><li>Item 3</li><li>Item 4</li><li>Item 5</li></ol>';
        $expect = [
            new SectionedParagraph('<ol class="lowerAlpha" start="3"><li>Item 3</li></ol>', 0, 0),
            new SectionedParagraph('<ol class="lowerAlpha" start="4"><li>Item 4</li></ol>', 0, 1),
            new SectionedParagraph('<ol class="lowerAlpha" start="5"><li>Item 5</li></ol>', 0, 2),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testSectioning(): void
    {
        $orig   = '<p>Test1</p><p>Test <strong>2</strong> Test</p>
<p>Some<br>
Line Break</p>
<blockquote><p>Quote 1</p>
<p>Quote 2</p></blockquote>
<p>Normal Paragraph</p>
<ul><li>Line 1</li>
<li>Line 2<br>Line 2, part 2</li>
<li>Line 3<strong>Strong</strong></li></ul><p>End</p>';
        $expect = [
            new SectionedParagraph('<p>Test1</p>', 0, 0),
            new SectionedParagraph('<p>Test <strong>2</strong> Test</p>', 1, 1),
            new SectionedParagraph('<p>Some<br>
Line Break</p>', 2, 2),
            new SectionedParagraph('<blockquote><p>Quote 1</p></blockquote>', 3, 3),
            new SectionedParagraph('<blockquote><p>Quote 2</p></blockquote>', 4, 4),
            new SectionedParagraph('<p>Normal Paragraph</p>', 5, 5),
            new SectionedParagraph('<ul><li>Line 1</li></ul>', 6, 6),
            new SectionedParagraph('<ul><li>Line 2<br>
Line 2, part 2</li></ul>', 6, 7),
            new SectionedParagraph('<ul><li>Line 3<strong>Strong</strong></li></ul>', 6, 8),
            new SectionedParagraph('<p>End</p>', 7, 9),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testLinkAttributeEncoding(): void
    {
        $orig   = '<p><a href="http://www.example.org?datum=20150724&amp;ausgabe=an-d">Test</a></p>';
        $expect = [new SectionedParagraph('<p><a href="http://www.example.org?datum=20150724&amp;ausgabe=an-d">Test</a></p>', 0, 0)];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNestedLists(): void
    {
        $orig   = '<ul>
    <li>Normal item</li>
    <li>
        <ol class="lowerAlpha">
            <li>Nested 1</li>
            <li>Nested 2<br>Line 3</li>
        </ol>
    </li>
    <li>Normal again</li>
</ul>
<ol class="decimalCircle">
    <li>Normal item</li>
    <li>
        <ul>
            <li>Nested 1</li>
            <li>Nested 2<br>Line 3</li>
        </ul>
    </li>
    <li>Normal again</li>
</ol>';
        $expect = [
            new SectionedParagraph('<ul><li>Normal item</li></ul>', 0, 0),
            new SectionedParagraph('<ul><li><ol class="lowerAlpha"><li>Nested 1</li><li>Nested 2<br>' . "\n" . 'Line 3</li></ol></li></ul>', 0, 1),
            new SectionedParagraph('<ul><li>Normal again</li></ul>', 0, 2),
            new SectionedParagraph('<ol class="decimalCircle" start="1"><li>Normal item</li></ol>', 1, 3),
            new SectionedParagraph('<ol class="decimalCircle" start="2"><li><ul><li>Nested 1</li><li>Nested 2<br>' . "\n" . 'Line 3</li></ul></li></ol>', 1, 4),
            new SectionedParagraph('<ol class="decimalCircle" start="3"><li>Normal again</li></ol>', 1, 5),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNonStandardOl1(): void
    {
        $orig = '<ol><li>Item 1</li><li value="3">Item 2</li><li>Item 3</li></ol>';
        $expect = [
            new SectionedParagraph('<ol start="1"><li>Item 1</li></ol>', 0, 0),
            new SectionedParagraph('<ol start="3"><li value="3">Item 2</li></ol>', 0, 1),
            new SectionedParagraph('<ol start="4"><li>Item 3</li></ol>', 0, 2),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNonStandardOl2(): void
    {
        $orig = '<ol><li>Item 1</li><li value="3b">Item 2</li><li>Item 3</li></ol>';
        $expect = [
            new SectionedParagraph('<ol start="1"><li>Item 1</li></ol>', 0, 0),
            new SectionedParagraph('<ol start="2"><li value="3b">Item 2</li></ol>', 0, 1),
            new SectionedParagraph('<ol start="3"><li>Item 3</li></ol>', 0, 2),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNonStandardOl3(): void
    {
        $orig = '<ol><li>Item 1</li><li value="E">Item 2</li><li>Item 3</li></ol>';
        $expect = [
            new SectionedParagraph('<ol start="1"><li>Item 1</li></ol>', 0, 0),
            new SectionedParagraph('<ol start="5"><li value="E">Item 2</li></ol>', 0, 1),
            new SectionedParagraph('<ol start="6"><li>Item 3</li></ol>', 0, 2),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testOlsWithClassess(): void
    {
        $orig = '<ol class="lowerAlpha"><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol>';
        $expect = [
            new SectionedParagraph('<ol class="lowerAlpha" start="1"><li>Item 1</li></ol>', 0, 0),
            new SectionedParagraph('<ol class="lowerAlpha" start="2"><li>Item 2</li></ol>', 0, 1),
            new SectionedParagraph('<ol class="lowerAlpha" start="3"><li>Item 3</li></ol>', 0, 2),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNoSplitLists(): void
    {
        $orig   = '<p>Test1</p><p>Test <strong>2</strong> Test</p>
<p>Some<br>
Line Break</p>
<blockquote><p>Quote 1</p>
<p>Quote 2</p></blockquote>
<p>Normal Paragraph</p>
<ul><li>Line 1</li>
<li>Line 2<br>Line 2, part 2</li>
<li>Line 3<strong>Strong</strong></li></ul><p>End</p>';
        $expect = [
            new SectionedParagraph('<p>Test1</p>', 0, null),
            new SectionedParagraph('<p>Test <strong>2</strong> Test</p>', 1, null),
            new SectionedParagraph('<p>Some<br>
Line Break</p>', 2, null),
            new SectionedParagraph('<blockquote><p>Quote 1</p></blockquote>', 3, null),
            new SectionedParagraph('<blockquote><p>Quote 2</p></blockquote>', 4, null),
            new SectionedParagraph('<p>Normal Paragraph</p>', 5, null),
            new SectionedParagraph('<ul><li>Line 1</li><li>Line 2<br>
Line 2, part 2</li><li>Line 3<strong>Strong</strong></li></ul>', 6, null),
            new SectionedParagraph('<p>End</p>', 7, null),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig, false);
        $this->assertEquals($expect, $out);
    }

    public function testLiPSomething(): void
    {
        // From https://bdk.antragsgruen.de/39/motion/133/amendment/323
        $orig   = '<ul>
<li>
	<p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>
	.</li>
</ul>';
        $expect = [new SectionedParagraph('<ul><li><p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>' . "\n" . '.</li></ul>', 0, 0)];
        $orig   = HTMLTools::cleanSimpleHtml($orig);
        $out    = HTMLTools::sectionSimpleHTML($orig, false);
        $this->assertEquals($expect, $out);
    }

    public function testPre(): void
    {
        $orig   = '<pre>llkj
lkj lkj    lkj
oii</pre><pre>llkj
lkj lkj    lkj
oii</pre>';
        $expect = [
            new SectionedParagraph('<pre>llkj
lkj lkj    lkj
oii</pre>', 0, null),
            new SectionedParagraph('<pre>llkj
lkj lkj    lkj
oii</pre>', 1, null),
        ];
        $orig   = HTMLTools::cleanSimpleHtml($orig);
        $out    = HTMLTools::sectionSimpleHTML($orig, false);
        $this->assertEquals($expect, $out);

        $orig   = '<blockquote><pre>llkj
lkj lkj    lkj
oii</pre><pre>llkj
lkj lkj    lkj
oii</pre></blockquote>';
        $expect = [
            new SectionedParagraph('<blockquote><pre>llkj
lkj lkj    lkj
oii</pre></blockquote>', 0, null),
            new SectionedParagraph('<blockquote><pre>llkj
lkj lkj    lkj
oii</pre></blockquote>', 1, null),
        ];
        $orig   = HTMLTools::cleanSimpleHtml($orig);
        $out    = HTMLTools::sectionSimpleHTML($orig, false);
        $this->assertEquals($expect, $out);


        $orig   = '<ul><li><pre>llkj
lkj lkj    lkj
oii</pre></li><li><pre>llkj
lkj lkj    lkj
oii</pre><p>More</p><pre>PRE</pre></li></ul>';
        $expect = [
            new SectionedParagraph('<ul><li><pre>llkj
lkj lkj    lkj
oii</pre></li></ul>', 0, 0),
            new SectionedParagraph('<ul><li><pre>llkj
lkj lkj    lkj
oii</pre><p>More</p><pre>PRE</pre></li></ul>', 0, 1),
        ];
        $orig   = HTMLTools::cleanSimpleHtml($orig);
        $out    = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments1(): void
    {
        $orig   = '<p>Test</p><ul><li>Item 1</li></ul><ul><li>Item 2</li></ul><ul><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>';
        $expect = '<p>Test</p><ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments2(): void
    {
        $orig   = "<p>Test</p>\n<ul>\n <li>Item 1</li></ul> \n <ul><li>Item 2</li></ul>\n<ul><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>";
        $expect = '<p>Test</p><ul>' . "\n" . ' <li>Item 1</li><li>Item 2</li><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments3(): void
    {
        $orig   = '<p>Test</p><ol><li>Item 1</li></ol><ol><li>Item 2</li></ol><ol><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $expect = '<p>Test</p><ol><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments4(): void
    {
        $orig   = '<p>Test</p><ol><li>Item 1</li></ol><ol start="3"><li>Item 2</li></ol><ol start="4"><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $expect = '<p>Test</p><ol><li>Item 1</li></ol><ol start="3"><li>Item 2</li><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragmentsWithWhitespaces(): void
    {
        $orig = '
<ul>
<li>
<p>Line 1.</p>
</li>
</ul>
<ul>
<li>
<p>Line 2.</p>
</li>
</ul>
<ul>
<li>
<p>Line 3.</p>
</li>
</ul>
<ul>
<li>
<p>Line 4.</p>
</li>
</ul>';
        $expect = '<ul>
<li>
<p>Line 1.</p>
</li>

<li>
<p>Line 2.</p>
</li>

<li>
<p>Line 3.</p>
</li>

<li>
<p>Line 4.</p>
</li>
</ul>';
        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testAdjacentLists(): void
    {
        $orig   = '<ul>
    <li>Item 1</li>
    <li>Item 2</li>
</ul><ul>
    <li>Item 3</li>
    <li>Item 4</li>
</ul>';
        $expect = [
            new SectionedParagraph('<ul><li>Item 1</li></ul>', 0, 0),
            new SectionedParagraph('<ul><li>Item 2</li></ul>', 0, 1),
            new SectionedParagraph('<ul><li>Item 3</li></ul>', 1, 2),
            new SectionedParagraph('<ul><li>Item 4</li></ul>', 1, 3),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNestedBlockElements(): void
    {
        $orig   = '<blockquote><div><p>Test 1</p><p>Test 2</p></div><div><p>Test 3</p><p>Test 4</p></div></blockquote>';
        $expect = [
            new SectionedParagraph('<blockquote><p>Test 1</p></blockquote>', 0, 0),
            new SectionedParagraph('<blockquote><p>Test 2</p></blockquote>', 1, 1),
            new SectionedParagraph('<blockquote><p>Test 3</p></blockquote>', 2, 2),
            new SectionedParagraph('<blockquote><p>Test 4</p></blockquote>', 3, 3),
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }
}
