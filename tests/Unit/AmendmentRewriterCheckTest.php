<?php

namespace Tests\Unit;

use app\components\diff\AmendmentRewriter;
use app\models\SectionedParagraph;
use Tests\Support\Helper\TestBase;

class AmendmentRewriterCheckTest extends TestBase
{
    public function testListInsertedAndDeleted1(): void
    {
        $oldHtml       = '<ul><li>List item 1</li><li>List item 2</li><li>List item 3</li><li>List item 5</li></ul>';
        $amendmentHtml = '<ul><li>List item 1</li><li>List item 2</li><li>List item 5</li></ul>';
        $newHtml       = '<ul><li>List item 1</li><li>List item 2</li><li>List item 3</li><li>List item 4</li><li>List item 5</li></ul>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $amendmentHtml, $newHtml);
        $this->assertTrue($rewritable);
    }

    public function testListInsertedAndDelete2(): void
    {
        $oldHtml       = '<ul><li>List item 1</li><li>List item 2</li><li>List item 3</li><li>List item 5</li></ul>';
        $amendmentHtml = '<ul><li>List item 1</li><li>List item 2</li><li>List item 5</li></ul>';
        $newHtml       = '<ul><li>List item 1</li><li>List item 2</li><li>List item ins 3</li><li>List item 5</li></ul>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertFalse($rewritable);
        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $amendmentHtml, $newHtml);
        $this->assertFalse($rewritable);
    }

    public function testLineInserted1(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>A new line</p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 5</p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
    }

    public function testBasic1(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 456 <STRONG>STRONG</STRONG></p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <STRONG>STRONG</STRONG></p>' . '<p>Test 5</p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $amendmentHtml, $newHtml);
        $this->assertTrue($rewritable);
    }

    public function testBasic2(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 456 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 124 <strong>STRONG</strong></p>' . '<p>Test 4</p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertFalse($rewritable);
        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $amendmentHtml, $newHtml);
        $this->assertFalse($rewritable);
    }

    public function testCollidingLineInserted1(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 3</p>';
        $amendmentHtml = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>A new line</p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 5</p>';

        $colliding = AmendmentRewriter::getCollidingParagraphs($oldHtml, $newHtml, $amendmentHtml);
        $this->assertEquals([
            1 => [
                'text' => '<p>Test 4</p>',
                'amendmentDiff' => '<p>Test <del>3</del><ins>4</ins></p>',
                'motionNewDiff' => '<p>Test <del>3</del><ins>5</ins></p>',
            ]
        ], $colliding);
    }

    public function testAffectedAddingLines(): void
    {
        $oldSections = [
            new SectionedParagraph('<p>The old line</p>', 0, 0),
        ];
        $newSections = [
            new SectionedParagraph('<p>Inserted before</p>', 0, 0),
            new SectionedParagraph('<p>Inserted before2</p>', 1, 1),
            new SectionedParagraph('<p>The old line</p>', 2, 2),
            new SectionedParagraph('<p>Inserted after</p>', 3, 3),
        ];
        $affected    = AmendmentRewriter::computeAffectedParagraphs($oldSections, $newSections, true);
        $this->assertCount(1, $affected);
        $this->assertEquals('<p><ins>Inserted before</ins></p><p class="inserted">Inserted before2</p><p>The old line</p><p><ins>Inserted after</ins></p>', $affected[0]);
    }

    public function testInParagraph1(): void
    {
        $oldHtml       = '<p>Test 123 Bla <strong>STRONG</strong> Some text to circumvent change quota</p>';
        $amendmentHtml = '<p>Bla<strong>STRONG</strong> Some text to circumvent change quota</p>';
        $newHtml       = '<p>Test 123 Bla <strong>STRONG 2</strong> Some text to circumvent change quota</p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $amendmentHtml, $newHtml);
        $this->assertTrue($rewritable);
    }

    public function testInParagraph2(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>';
        $amendmentHtml = '<p>Test2 123 <strong>STRONG</strong></p>';
        $newHtml       = '<p>Test 123 <strong>STRONG 2</strong></p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
    }

    public function testInParagraph3(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>';
        $newHtml       = '<p>Test2 123 <strong>STRONG</strong></p>';
        $amendmentHtml = '<p>Test 123 <strong>STRONG 2</strong></p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
    }

    public function testInParagraph4(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>';
        $amendmentHtml = '<p>Test2 123 <strong>STRONG</strong></p>';
        $newHtml       = '<p>Test3 123 <strong>STRONG 2</strong></p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertFalse($rewritable);
    }

    public function testInParagraph5(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>';
        $amendmentHtml = '<p>Test2 123 <strong>STRONG</strong></p>';
        $newHtml       = '<p>Test2 123 <strong>STRONG 2</strong></p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
    }

    public function testInParagraph6(): void
    {
        $oldHtml       = '<p>Test 123 Bla 123 <strong>STRONG</strong></p>';
        $amendmentHtml = '<p>Bla 123 <strong>STRONG</strong></p>';
        $newHtml       = '<p>Bla 123 <strong>STR</strong></p>';

        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertTrue($rewritable);
        $rewritable = AmendmentRewriter::canRewrite($oldHtml, $amendmentHtml, $newHtml);
        $this->assertTrue($rewritable);
    }
}
