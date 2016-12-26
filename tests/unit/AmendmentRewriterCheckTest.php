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

    /**
     */
    public function testCollidingLineInserted1()
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 3</p>';
        $amendmentHtml = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>A new line</p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 5</p>';

        $colliding = AmendmentRewriter::getCollidingParagraphs($oldHtml, $newHtml, $amendmentHtml);
        $this->assertEquals([
            1 => '<p>Test 4</p>'
        ], $colliding);
    }

    /**
     */
    public function testAffectedAddingLines()
    {
        $oldSections = [
            '<p>The old line</p>'
        ];
        $newSections = [
            '<p>Inserted before</p>',
            '<p>Inserted before2</p>',
            '<p>The old line</p>',
            '<p>Inserted after</p>',
        ];
        $affected = AmendmentRewriter::computeAffectedParagraphs($oldSections, $newSections, true);
        $this->assertEquals(1, count($affected));
        $this->assertEquals('<p><ins>Inserted before</ins></p><p class="inserted">Inserted before2</p><p>The old line</p><p><ins>Inserted after</ins></p>', $affected[0]);
    }
}
