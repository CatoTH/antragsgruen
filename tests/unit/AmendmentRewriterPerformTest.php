<?php

namespace unit;

use app\components\diff\AmendmentRewriter;

class AmendmentRewriterPerformTest extends TestBase
{
    /**
     */
    public function testBasic1()
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 456 <STRONG>STRONG</STRONG></p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <STRONG>STRONG</STRONG></p>' . '<p>Test 5</p>';

        $rewritten = AmendmentRewriter::performRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertEquals('<p>Test 456 <strong>STRONG</strong></p>' . "\n" . '<p>Test 5</p>', $rewritten);
    }
}
