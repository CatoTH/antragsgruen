<?php

namespace Tests\Unit;

use app\components\HTMLTools;
use Tests\Support\Helper\TestBase;

class HTMLLiValuesTest extends TestBase
{
    public function testExplicitlySetLiValues_testExample1(): void
    {
        $orig = '<ol><li>Item 1</li><li value="3">Item 2</li><li>Item 3</li></ol>';
        $expect = '<ol><li value="1">Item 1</li><li value="3">Item 2</li><li value="4">Item 3</li></ol>';

        $out  = HTMLTools::explicitlySetLiValues($orig);
        $this->assertEquals($expect, $out);
    }

    public function testExplicitlySetLiValues_testExample2(): void
    {
        $orig = '<div><ol class="deleted" start="4">
<li>Test 2
<ol class="decimalCircle">
<li>Test a</li>
<li value="g">Test c</li>
<li value="i/">Test d</li>
<li>Test 9</li>
</ol>
</li></ol>
<ol class="inserted" start="2"><li>Test3</li></ol>
<ol class="deleted lowerAlpha" start="5"><li>Test3</li></ol>
</div>';
        $expect = '<div><ol class="deleted" start="4">
<li value="4">Test 2
<ol class="decimalCircle">
<li value="1">Test a</li>
<li value="g">Test c</li>
<li value="i/">Test d</li>
<li value="9">Test 9</li>
</ol>
</li></ol>
<ol class="inserted" start="2"><li value="2">Test3</li></ol>
<ol class="deleted lowerAlpha" start="5"><li value="e">Test3</li></ol>
</div>';

        $out  = HTMLTools::explicitlySetLiValues($orig);
        $this->assertEquals($expect, $out);
    }

    public function testGetLiValue_alphabetic1(): void
    {
        $this->assertSame('A', HTMLTools::getLiValue(1, null, HTMLTools::OL_UPPER_ALPHA));
        $this->assertSame('Z', HTMLTools::getLiValue(26, null, HTMLTools::OL_UPPER_ALPHA));
        $this->assertSame('AA', HTMLTools::getLiValue(27, null, HTMLTools::OL_UPPER_ALPHA));
        $this->assertSame('BA', HTMLTools::getLiValue(53, null, HTMLTools::OL_UPPER_ALPHA));
    }

    public function testGetLiValue_alphabetic2(): void
    {
        $this->assertSame('a', HTMLTools::getLiValue(1, null, HTMLTools::OL_LOWER_ALPHA));
        $this->assertSame('z', HTMLTools::getLiValue(26, null, HTMLTools::OL_LOWER_ALPHA));
        $this->assertSame('aa', HTMLTools::getLiValue(27, null, HTMLTools::OL_LOWER_ALPHA));
        $this->assertSame('ba', HTMLTools::getLiValue(53, null, HTMLTools::OL_LOWER_ALPHA));
    }
}
