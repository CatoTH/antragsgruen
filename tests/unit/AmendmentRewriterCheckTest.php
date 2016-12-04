<?php

namespace unit;

use app\components\diff\AmendmentRewriter;

class AmendmentRewriterCheckTest extends TestBase
{
    /**
     */
    public function testLineInserted1()
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>A new line</p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 5</p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
    }
    /**
     */
    public function testBasic1()
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 456 <STRONG>STRONG</STRONG></p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <STRONG>STRONG</STRONG></p>' . '<p>Test 5</p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
    }

    /**
     */
    public function testBasic2()
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 456 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 124 <strong>STRONG</strong></p>' . '<p>Test 4</p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertFalse($rewritable);
    }
}
