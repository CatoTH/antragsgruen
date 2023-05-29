<?php

namespace unit;

use app\components\HTMLTools;
use Codeception\Specify;

class HTMLSectioningTest extends TestBase
{
    use Specify;



    public function testOlsWithStart()
    {
        $orig = '<ol class="lowerAlpha" start="3"><li>Item 3</li><li>Item 4</li><li>Item 5</li></ol>';
        $expect = [
            '<ol class="lowerAlpha" start="3"><li>Item 3</li></ol>',
            '<ol class="lowerAlpha" start="4"><li>Item 4</li></ol>',
            '<ol class="lowerAlpha" start="5"><li>Item 5</li></ol>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testSectioning()
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
            '<p>Test1</p>',
            '<p>Test <strong>2</strong> Test</p>',
            '<p>Some<br>
Line Break</p>',
            '<blockquote><p>Quote 1</p></blockquote>',
            '<blockquote><p>Quote 2</p></blockquote>',
            '<p>Normal Paragraph</p>',
            '<ul><li>Line 1</li></ul>',
            '<ul><li>Line 2<br>
Line 2, part 2</li></ul>',
            '<ul><li>Line 3<strong>Strong</strong></li></ul>',
            '<p>End</p>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testLinkAttributeEncoding()
    {
        $orig   = '<p><a href="http://www.example.org?datum=20150724&amp;ausgabe=an-d">Test</a></p>';
        $expect = ['<p><a href="http://www.example.org?datum=20150724&amp;ausgabe=an-d">Test</a></p>'];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNestedLists()
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
            '<ul><li>Normal item</li></ul>',
            '<ul><li><ol class="lowerAlpha"><li>Nested 1</li><li>Nested 2<br>' . "\n" . 'Line 3</li></ol></li></ul>',
            '<ul><li>Normal again</li></ul>',
            '<ol class="decimalCircle" start="1"><li>Normal item</li></ol>',
            '<ol class="decimalCircle" start="2"><li><ul><li>Nested 1</li><li>Nested 2<br>' . "\n" . 'Line 3</li></ul></li></ol>',
            '<ol class="decimalCircle" start="3"><li>Normal again</li></ol>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNonStandardOl1()
    {
        $orig = '<ol><li>Item 1</li><li value="3">Item 2</li><li>Item 3</li></ol>';
        $expect = [
            '<ol start="1"><li>Item 1</li></ol>',
            '<ol start="3"><li value="3">Item 2</li></ol>',
            '<ol start="4"><li>Item 3</li></ol>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNonStandardOl2()
    {
        $orig = '<ol><li>Item 1</li><li value="3b">Item 2</li><li>Item 3</li></ol>';
        $expect = [
            '<ol start="1"><li>Item 1</li></ol>',
            '<ol start="2"><li value="3b">Item 2</li></ol>',
            '<ol start="3"><li>Item 3</li></ol>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNonStandardOl3()
    {
        $orig = '<ol><li>Item 1</li><li value="E">Item 2</li><li>Item 3</li></ol>';
        $expect = [
            '<ol start="1"><li>Item 1</li></ol>',
            '<ol start="5"><li value="E">Item 2</li></ol>',
            '<ol start="6"><li>Item 3</li></ol>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testOlsWithClassess()
    {
        $orig = '<ol class="lowerAlpha"><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol>';
        $expect = [
            '<ol class="lowerAlpha" start="1"><li>Item 1</li></ol>',
            '<ol class="lowerAlpha" start="2"><li>Item 2</li></ol>',
            '<ol class="lowerAlpha" start="3"><li>Item 3</li></ol>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNoSplitLists()
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
            '<p>Test1</p>',
            '<p>Test <strong>2</strong> Test</p>',
            '<p>Some<br>
Line Break</p>',
            '<blockquote><p>Quote 1</p></blockquote>',
            '<blockquote><p>Quote 2</p></blockquote>',
            '<p>Normal Paragraph</p>',
            '<ul><li>Line 1</li><li>Line 2<br>
Line 2, part 2</li><li>Line 3<strong>Strong</strong></li></ul>',
            '<p>End</p>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig, false);
        $this->assertEquals($expect, $out);
    }

    public function testLiPSomething()
    {
        // From https://bdk.antragsgruen.de/39/motion/133/amendment/323
        $orig   = '<ul>
<li>
	<p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>
	.</li>
</ul>';
        $expect = ['<ul><li><p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>' . "\n" . '.</li></ul>'];
        $orig   = HTMLTools::cleanSimpleHtml($orig);
        $out    = HTMLTools::sectionSimpleHTML($orig, false);
        $this->assertEquals($expect, $out);
    }

    public function testPre()
    {
        $orig   = '<pre>llkj
lkj lkj    lkj
oii</pre><pre>llkj
lkj lkj    lkj
oii</pre>';
        $expect = [
            '<pre>llkj
lkj lkj    lkj
oii</pre>',
            '<pre>llkj
lkj lkj    lkj
oii</pre>'
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
            '<blockquote><pre>llkj
lkj lkj    lkj
oii</pre></blockquote>',
            '<blockquote><pre>llkj
lkj lkj    lkj
oii</pre></blockquote>'
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
            '<ul><li><pre>llkj
lkj lkj    lkj
oii</pre></li></ul>',
            '<ul><li><pre>llkj
lkj lkj    lkj
oii</pre><p>More</p><pre>PRE</pre></li></ul>'
        ];
        $orig   = HTMLTools::cleanSimpleHtml($orig);
        $out    = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments1()
    {
        $orig   = '<p>Test</p><ul><li>Item 1</li></ul><ul><li>Item 2</li></ul><ul><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>';
        $expect = '<p>Test</p><ul><li>Item 1</li><li>Item 2</li><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments2()
    {
        $orig   = "<p>Test</p>\n<ul>\n <li>Item 1</li></ul> \n <ul><li>Item 2</li></ul>\n<ul><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>";
        $expect = '<p>Test</p><ul>' . "\n" . ' <li>Item 1</li><li>Item 2</li><li>Item 3</li></ul><p>Test 2</p><ul><li>Item 1</li></ul>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments3()
    {
        $orig   = '<p>Test</p><ol><li>Item 1</li></ol><ol><li>Item 2</li></ol><ol><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $expect = '<p>Test</p><ol><li>Item 1</li><li>Item 2</li><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragments4()
    {
        $orig   = '<p>Test</p><ol><li>Item 1</li></ol><ol start="3"><li>Item 2</li></ol><ol start="4"><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $expect = '<p>Test</p><ol><li>Item 1</li></ol><ol start="3"><li>Item 2</li><li>Item 3</li></ol><p>Test 2</p><ol><li>Item 1</li></ol>';
        $out    = HTMLTools::removeSectioningFragments($orig);
        $this->assertEquals($expect, $out);
    }

    public function testRemoveSplitFragmentsWithWhitespaces() {
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
}
