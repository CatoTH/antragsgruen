<?php

namespace unit;

use app\components\HTMLTools;
use Yii;
use Codeception\Specify;

class HTMLSectioningTest extends TestBase
{
    use Specify;

    /**
     */
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

    /**
     */
    public function testLinkAttributeEncoding()
    {
        $orig   = '<a href="http://www.example.org?datum=20150724&amp;ausgabe=an-d">Test</a>';
        $expect = ['<a href="http://www.example.org?datum=20150724&amp;ausgabe=an-d">Test</a>'];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
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

    /**
     */
    public function testLiPSomething()
    {
        return;
        // https://bdk.antragsgruen.de/39/motion/133/amendment/323
        // Depends on: improving sectioning, also required for https://github.com/CatoTH/antragsgruen/issues/120

        $orig = '<ul>
<li>
	<p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>
	.</li>
</ul>';
        $expect = ['<ul><li><p>Die Mobilisierung der Mittel für den internationalen Klimaschutz ist zum allergroßten Teil öffentliche Aufgabe, denn Unternehmen investieren nicht in schwach entwickelte oder fragile Staaten die meist ohnehin am stärksten vom Klimawandel betroffen sind. Die Wirtschaft ist unter starken menschenrechtlichen.</p>.</li></ul>'];
        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig, false);
        $this->assertEquals($expect, $out);
    }
}
