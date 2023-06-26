<?php

namespace Tests\Unit;

use app\components\HTMLTools;
use Tests\Support\Helper\TestBase;

class HTMLToolsStripInsDelMarkersTest extends TestBase
{
    public function testInsDel(): void
    {
        $orig     = '<strong>Test <ins>Inserted <em>EM</em></ins><del>old</del></strong><br>';
        $expected = '<strong>Test Inserted <em>EM</em></strong><br>';
        $out      = HTMLTools::stripInsDelMarkers($orig);
        $this->assertEquals($expected, $out);
    }

    public function testClasses(): void
    {
        $orig     = '<ul><li>Test 1</li><li class="inserted">test <strong>2</strong></li><li class="deleted">Test 3</li></ul><div class="inserted underlined">Another line</div>';
        $expected = '<ul><li>Test 1</li><li>test <strong>2</strong></li></ul><div class="underlined">Another line</div>';
        $out      = HTMLTools::stripInsDelMarkers($orig);
        $this->assertEquals($expected, $out);
    }
}
