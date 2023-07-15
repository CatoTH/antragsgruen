<?php

declare(strict_types=1);

namespace Tests\Unit;

use app\components\HTMLTools;
use Tests\Support\Helper\TestBase;

class HTMLToolsTrimHtmlTest extends TestBase
{
    public function testShortenRegular(): void
    {
        $orig     = '<ul><li><p>Test 1234567890 1234567890 1234567890 1234567890</p></li></ul>';
        $expected = '<ul><li><p>Test 1234567890 123…</p></li></ul>';
        $out      = HTMLTools::trimHtml($orig, 70);
        $this->assertEquals($expected, $out);
    }

    public function testShortenSplitInClosingTag(): void
    {
        $orig     = '<ul><li><p>Test 1234567890 1234567890 1234567890 1234567890</p></li></ul>';
        $expected = '<ul><li><p>Test 1234567890 1234567890 1234567890 1234567890</p></li></ul>';
        $out      = HTMLTools::trimHtml($orig, 102);
        $this->assertEquals($expected, $out);
    }
}
