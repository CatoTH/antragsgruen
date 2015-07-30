<?php

namespace unit;

use app\components\LineSplitter;
use Codeception\Specify;

class MotionPara2LinesTest extends TestBase
{
    use Specify;

    /**
     */
    public function testUl()
    {
        $orig   = '<ul><li>No. 1</li></ul>';
        $expect = [
            '<ul><li>###LINENUMBER###No. 1</li></ul>',
        ];

        $out = LineSplitter::motionPara2lines($orig, true, 80);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testBockquote()
    {
        $orig   = '<blockquote><p>No. 1</p></blockquote>';
        $expect = [
            '<blockquote><p>###LINENUMBER###No. 1</p></blockquote>',
        ];

        $out = LineSplitter::motionPara2lines($orig, true, 80);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testOl()
    {
        $orig   = '<ol start="2"><li>No. 1</li></ol>';
        $expect = [
            '<ol start="2"><li>###LINENUMBER###No. 1</li></ol>',
        ];

        $out = LineSplitter::motionPara2lines($orig, true, 80);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testForceLinebreak()
    {
        $orig   = '<p><br><strong>Demokratie und Freiheit </strong><br>' . "\r\n" .
            'Demokratie und Freiheit gehören untrennbar zusammen.';
        $expect = [
            '<p>###LINENUMBER######FORCELINEBREAK###',
            '###LINENUMBER###<strong>Demokratie und Freiheit </strong>###FORCELINEBREAK###',
            '###LINENUMBER###Demokratie und Freiheit gehören untrennbar zusammen.',
        ];
        $out = LineSplitter::motionPara2lines($orig, true, 80);
        $this->assertEquals($expect, $out);
    }
}
