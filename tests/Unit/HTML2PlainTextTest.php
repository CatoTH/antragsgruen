<?php

namespace Tests\Unit;

use app\components\HTMLTools;
use Tests\Support\Helper\TestBase;

class HTML2PlainTextTest extends TestBase
{
    /**
     */
    public function testCase1(): void
    {
        $orig   = '<h1>Test</h1><div>Bla</div><ul><li>Test 1</li><li>Test 2</li></ul>';
        $expect = "Test\nBla\n* Test 1\n* Test 2";
        $out    = HTMLTools::toPlainText($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testCase2(): void
    {
        $orig   = '<h3>Antragstext</h3><h4 class="lineSummary">In Zeile 1:</h4><div><p><del style="color:#FF0000;text-decoration:line-through;">Test</del><ins style="color:#008000;text-decoration:underline;">sdlfklkj</ins></p></div>';
        $expect = "Antragstext\nIn Zeile 1:\n[LÖSCHUNG]Test[/LÖSCHUNG][EINFÜGUNG]sdlfklkj[/EINFÜGUNG]";
        $out    = HTMLTools::toPlainText($orig);
        $this->assertEquals($expect, $out);
    }
}
