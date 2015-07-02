<?php

namespace unit;

use app\components\HTMLTools;
use Yii;
use Codeception\Specify;

class HTMLSectioningTest extends TestBase
{
    use Specify;

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
            '<ul><li>Line 2<br>' . "\n" . 'Line 2, part 2</li></ul>',
            '<ul><li>Line 3<strong>Strong</strong></li></ul>',
            '<p>End</p>',
        ];

        $orig = HTMLTools::cleanSimpleHtml($orig);
        $out  = HTMLTools::sectionSimpleHTML($orig);
        $this->assertEquals($out, $expect);
    }
}
