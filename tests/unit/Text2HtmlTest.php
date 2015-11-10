<?php

namespace unit;

use app\components\HTMLTools;
use Codeception\Specify;

class LineSplitTest extends TestBase
{
    use Specify;

    /**
     */
    public function testInsertLinks()
    {
        $orig   = 'http://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2
Link
http://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2';
        $expect = '<a rel="nofollow" href="http://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2">http://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2</a><br>
Link<br>
<a rel="nofollow" href="http://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2">http://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2</a>';
        $html   = HTMLTools::textToHtmlWithLink($orig);
        $this->assertEquals($expect, $html);

        $orig   = 'www.antragsgruen.de
www.antragsgruen.de/test2?';
        $expect = '<a rel="nofollow" href="http://www.antragsgruen.de">www.antragsgruen.de</a><br>
<a rel="nofollow" href="http://www.antragsgruen.de/test2">www.antragsgruen.de/test2</a>?';
        $html   = HTMLTools::textToHtmlWithLink($orig);
        $this->assertEquals($expect, $html);
    }
}
