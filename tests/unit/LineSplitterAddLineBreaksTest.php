<?php

namespace unit;

use app\components\LineSplitter;
use Codeception\Specify;

class LineSplitterAddLineBreaksTest extends TestBase
{
    use Specify;

    public function testRegularWithNumbers()
    {
        $htmlIn = '<p>###LINENUMBER###Line 1 ###LINENUMBER### Line 2</p><p>###LINENUMBER###Line3</p>';
        $expect = '<p><span class="lineNumber" data-line-number="3" aria-hidden="true"></span>Line 1 ' .
                  '<br><span class="lineNumber" data-line-number="4" aria-hidden="true"></span> Line 2</p><p>' .
                  '<span class="lineNumber" data-line-number="5" aria-hidden="true"></span>Line3</p>';

        $htmlOut = LineSplitter::replaceLinebreakPlaceholdersByMarkup($htmlIn, true, 3);
        $this->assertSame($expect, $htmlOut);
    }

    public function testRegularWithoutNumbers()
    {
        $htmlIn = '<p>###LINENUMBER###Line 1 ###LINENUMBER### Line 2</p><p>###LINENUMBER###Line3</p>';
        $expect = '<p>Line 1 <br> Line 2</p><p>Line3</p>';

        $htmlOut = LineSplitter::replaceLinebreakPlaceholdersByMarkup($htmlIn, false, 3);
        $this->assertSame($expect, $htmlOut);
    }

    public function testNestedListsWithNumbers()
    {
        $htmlIn = '<ul><li><p>###LINENUMBER###Line 1 ###LINENUMBER###Line 2</p><ol><li>###LINENUMBER###Line 3 ###LINENUMBER###Line 4</li></ol></li></ul>';
        $expect = '<ul><li><p><span class="lineNumber" data-line-number="3" aria-hidden="true"></span>Line 1 ' .
                  '<br><span class="lineNumber" data-line-number="4" aria-hidden="true"></span>Line 2</p>' .
                  '<ol><li><span class="lineNumber" data-line-number="5" aria-hidden="true"></span>Line 3 ' .
                  '<br><span class="lineNumber" data-line-number="6" aria-hidden="true"></span>Line 4</li></ol></li></ul>';

        $htmlOut = LineSplitter::replaceLinebreakPlaceholdersByMarkup($htmlIn, true, 3);
        $this->assertSame($expect, $htmlOut);
    }

    public function testNestedListsWithoutNumbers()
    {
        $htmlIn = '<ul><li><p>###LINENUMBER###Line 1 ###LINENUMBER###Line 2</p><ol><li>###LINENUMBER###Line 3 ###LINENUMBER###Line 4</li></ol></li></ul>';
        $expect = '<ul><li><p>Line 1 <br>Line 2</p>' .
                  '<ol><li>Line 3 <br>Line 4</li></ol></li></ul>';

        $htmlOut = LineSplitter::replaceLinebreakPlaceholdersByMarkup($htmlIn, false, 3);
        $this->assertSame($expect, $htmlOut);
    }
}
