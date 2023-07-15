<?php

namespace Tests\Unit;

use app\components\HTMLTools;
use Tests\Support\Helper\TestBase;

class Text2HtmlTest extends TestBase
{
    public function testInsertLinks1(): void
    {
        $orig   = 'https://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2
Link
https://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2';
        $expect = '<a rel="nofollow" href="https://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2">https://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2</a><br>
Link<br>
<a rel="nofollow" href="https://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2">https://stdparteitag.antragsgruen.localhost/std-parteitag/motion/2</a>';
        $html   = HTMLTools::textToHtmlWithLink($orig);
        $this->assertEquals($expect, $html);
    }
    public function testInsertLinks2(): void
    {
        // TODO should be HTTPS
        $orig   = 'www.antragsgruen.de
www.antragsgruen.de/test2?';
        $expect = '<a rel="nofollow" href="http://www.antragsgruen.de">www.antragsgruen.de</a><br>
<a rel="nofollow" href="http://www.antragsgruen.de/test2">www.antragsgruen.de/test2</a>?';
        $html   = HTMLTools::textToHtmlWithLink($orig);
        $this->assertEquals($expect, $html);
    }
}
