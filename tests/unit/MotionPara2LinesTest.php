<?php

namespace unit;

use app\components\LineSplitter;
use Codeception\Specify;
use Codeception\Util\Autoload;

Autoload::addNamespace('unit', __DIR__);

class MotionPara2LinesTest extends TestBase
{
    use Specify;

    /**
     *
     */
    public function testSplit()
    {
        $this->specify(
            'Testing <ul>',
            function () {
                $in     = '<ul><li>No. 1</li></ul>';
                $expect = [
                    '<ul><li>###LINENUMBER###No. 1</li></ul>',
                ];

                $out = LineSplitter::motionPara2lines($in, true, 80);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing <blockquote>',
            function () {
                $in     = '<blockquote><p>No. 1</p></blockquote>';
                $expect = [
                    '<blockquote><p>###LINENUMBER###No. 1</p></blockquote>',
                ];

                $out = LineSplitter::motionPara2lines($in, true, 80);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing <ol>',
            function () {
                $in     = '<ol start="2"><li>No. 1</li></ol>';
                $expect = [
                    '<ol start="2"><li>###LINENUMBER###No. 1</li></ol>',
                ];

                $out = LineSplitter::motionPara2lines($in, true, 80);
                $this->assertEquals($expect, $out);
            }
        );
    }
}
