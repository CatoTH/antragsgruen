<?php

namespace unit;

use app\components\diff\MovingParagraphDetector;

class MovingParagraphDetectorTest extends TestBase
{
    /**
     */
    public function testTest1()
    {
        $diffParas = [
            '<p>A paragraph with no changes</p>',
            '<p>Another paragraph</p><p class="inserted">the paragraph to be moved</p>',
            '<p>Test</p>',
            '<p class="deleted">the paragraph to be moved</p>',
        ];
        $markedUp = MovingParagraphDetector::markupMovedParagraphs($diffParas);
        $this->assertEquals([
            '<p>A paragraph with no changes</p>',
            '<p>Another paragraph</p><p data-moving-partner-id="1" data-moving-partner-paragraph="3" class="inserted moved">the paragraph to be moved</p>',
            '<p>Test</p>',
            '<p data-moving-partner-id="1" data-moving-partner-paragraph="1" class="deleted moved">the paragraph to be moved</p>',
        ], $markedUp);
    }
}
