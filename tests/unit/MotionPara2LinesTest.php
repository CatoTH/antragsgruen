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
    public function testBlockquote()
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
        $out    = LineSplitter::motionPara2lines($orig, true, 80);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testWithDashInWord()
    {
        $orig   = '<p>nationalen Parlamente sowie die Rückkehr zur Gemeinschaftsmethode und eine EU-Kommissarin oder einen EU-Kommissar; er oder sie soll der Eurogruppe vorsitzen und mit allen WWU-relevanten Kompetenzen ausgestattet sein.</p>';
        $expect = [
            '<p>###LINENUMBER###nationalen Parlamente sowie die Rückkehr zur Gemeinschaftsmethode und eine EU-Kommissarin ',
            '###LINENUMBER###oder einen EU-Kommissar; er oder sie soll der Eurogruppe vorsitzen und mit allen WWU-',
            '###LINENUMBER###relevanten Kompetenzen ausgestattet sein.</p>'
        ];
        $out    = LineSplitter::motionPara2lines($orig, true, 92);

        $this->assertEquals($expect, $out);
    }
}
