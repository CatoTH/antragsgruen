<?php

namespace unit;

use app\components\HTMLTools;

class HTML2PlainTextTest extends TestBase
{
    /**
     */
    public function testCase1()
    {
        $orig   = '<h1>Test</h1><div>Bla</div><ul><li>Test 1</li><li>Test 2</li></ul>';
        $expect = "Test\nBla\n* Test 1\n* Test 2";
        $out    = HTMLTools::toPlainText($orig);
        $this->assertEquals($expect, $out);
    }
}