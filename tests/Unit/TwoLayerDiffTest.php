<?php

namespace Tests\Unit;

use app\components\diff\AmendmentSectionFormatter;
use app\components\diff\DataTypes\AffectedLineBlock;
use app\components\diff\DiffRenderer;
use app\components\diff\TwoLayerDiff;
use Tests\Support\Helper\TestBase;

class TwoLayerDiffTest extends TestBase
{
    /**
     * @param string[] $original
     * @param string[] $parent
     * @param string[] $child
     * @return string[]
     */
    private function combine(array $original, array $parent, array $child): array
    {
        $twoLayer = new TwoLayerDiff();
        $combined = $twoLayer->computeParagraphs($original, $parent, $child);
        $this->assertNotNull($combined);

        return $combined;
    }

    /**
     * @param string[] $original
     * @param string[] $parent
     * @param string[] $child
     * @return string[]
     */
    private function combineAndRender(array $original, array $parent, array $child): array
    {
        $twoLayer = new TwoLayerDiff();
        $rendered = $twoLayer->computeAndRenderParagraphs($original, $parent, $child, DiffRenderer::FORMATTING_CLASSES);
        $this->assertNotNull($rendered);

        return $rendered;
    }

    public function testUnchangedByChild(): void
    {
        // The child amendment proposes exactly what the parent amendment proposes
        // => everything is part of the outer layer, nothing of the inner one
        $rendered = $this->combineAndRender(
            ['<p>Test 123 aber das hier nicht</p>'],
            ['<p>Test 1234567 aber das hier</p>'],
            ['<p>Test 1234567 aber das hier</p>']
        );

        $this->assertCount(1, $rendered);
        $this->assertStringNotContainsString('<ins>', $rendered[0]);
        $this->assertStringNotContainsString('<del>', $rendered[0]);
        $this->assertStringContainsString('class="outer"', $rendered[0]);
    }

    public function testChangedInsertionAndUndeletion(): void
    {
        // The example from the feature request:
        // - the parent replaces "123" by "1234567", the child by "1234568"
        // - the parent deletes "nicht", the child only deletes "nich" and keeps the "t"
        $combined = $this->combine(
            ['<p>Test 123 aber das hier nicht</p>'],
            ['<p>Test 1234567 aber das hier </p>'],
            ['<p>Test 1234568 aber das hier t</p>']
        );

        $this->assertSame(
            '<p>Test ' .
            DiffRenderer::OUTER_DEL_START . '123' . DiffRenderer::OUTER_DEL_END .
            DiffRenderer::OUTER_INS_START . '123456' .
            DiffRenderer::DEL_START . '7' . DiffRenderer::DEL_END .
            DiffRenderer::INS_START . '8' . DiffRenderer::INS_END .
            DiffRenderer::OUTER_INS_END .
            ' aber das hier ' .
            DiffRenderer::OUTER_DEL_START . 'nicht' . DiffRenderer::OUTER_DEL_END .
            DiffRenderer::INS_START . 't' . DiffRenderer::INS_END .
            '</p>',
            $combined[0]
        );

        $rendered = $this->combineAndRender(
            ['<p>Test 123 aber das hier nicht</p>'],
            ['<p>Test 1234567 aber das hier </p>'],
            ['<p>Test 1234568 aber das hier t</p>']
        );
        $this->assertSame(
            '<p>Test <del class="outer">123</del><ins class="outer">123456<del>7</del><ins>8</ins></ins>' .
            ' aber das hier <del class="outer">nicht</del><ins>t</ins></p>',
            $rendered[0]
        );
    }

    public function testChildDropsInsertionOfParent(): void
    {
        // The parent inserts a word, the child does not want it
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet consetetur</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>']
        );

        $this->assertStringContainsString('<ins class="outer"><del>', $rendered[0]);
        $this->assertStringContainsString('consetetur', $rendered[0]);
    }

    public function testChildAddsOwnInsertion(): void
    {
        // The child inserts something at a place the parent did not touch => plain inner insertion
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet consetetur</p>'],
            ['<p>Lorem sed ipsum dolor sit amet consetetur</p>']
        );

        $this->assertStringContainsString('<ins>sed </ins>', $rendered[0]);
        // The insertion of the parent is untouched by the child and stays purely on the outer layer
        $this->assertStringContainsString('<ins class="outer"> consetetur</ins>', $rendered[0]);
    }

    public function testChildDeletesTextParentKept(): void
    {
        // Neither amendment touches "dolor", but the child deletes it => plain inner deletion
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet consetetur</p>'],
            ['<p>Lorem ipsum sit amet consetetur</p>']
        );

        $this->assertStringContainsString('<del>dolor </del>', $rendered[0]);
        $this->assertStringContainsString('<ins class="outer"> consetetur</ins>', $rendered[0]);
    }

    public function testLineNumbersArePreserved(): void
    {
        $original = ['<p>###LINENUMBER###Lorem ipsum dolor sit amet, ###LINENUMBER###consetetur sadipscing elitr</p>'];
        $rendered = $this->combineAndRender(
            $original,
            ['<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam</p>'],
            ['<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy</p>']
        );

        $this->assertSame(2, substr_count($rendered[0], '###LINENUMBER###'));
    }

    public function testWholeParagraphInsertedByParentAndChangedByChild(): void
    {
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing elitr</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing tempor</p>']
        );

        // The added paragraph belongs to the outer layer, the word the child replaced within it to the inner one
        $this->assertStringContainsString('class="insertedOuter"', $rendered[0]);
        $this->assertStringContainsString('<del>elitr</del>', $rendered[0]);
        $this->assertStringContainsString('<ins>tempor</ins>', $rendered[0]);
    }

    /**
     * Paragraphs that only the amended amendment changed are context, not a change of this amendment,
     * and must therefore not show up in the "only changed paragraphs" view.
     */
    public function testOnlyChangedLinesIgnoresTheOuterLayer(): void
    {
        $original = '<p>Lorem ipsum dolor sit amet</p>' . "\n" .
                    '<p>Consetetur sadipscing elitr</p>' . "\n" .
                    '<p>Sed diam nonumy eirmod tempor</p>';
        $parent   = '<p>Lorem ipsum dolor sit amet, consetetur</p>' . "\n" .
                    '<p>Consetetur sadipscing elitr</p>' . "\n" .
                    '<p>Sed diam nonumy eirmod tempor</p>';
        $child    = '<p>Lorem ipsum dolor sit amet, consetetur</p>' . "\n" .
                    '<p>Consetetur sadipscing elitr</p>' . "\n" .
                    '<p>Sed diam nonumy eirmod incididunt</p>';

        $formatter = new AmendmentSectionFormatter();
        $formatter->setTextOriginal($original);
        $formatter->setTextParent($parent);
        $formatter->setTextNew($child);
        $formatter->setFirstLineNo(1);

        $groups = $formatter->getTwoLayerDiffGroupsWithNumbers(80, DiffRenderer::FORMATTING_CLASSES, 0);
        $this->assertNotNull($groups);

        $text = implode("\n", array_map(fn (AffectedLineBlock $block) => $block->text, $groups));
        $this->assertStringContainsString('incididunt', $text);
        // The change of the amended amendment is in a paragraph this amendment does not touch at all
        $this->assertStringNotContainsString('consetetur', $text);
    }

    public function testWholeParagraphDeletedByParentAndRestoredByChild(): void
    {
        $rendered = $this->combineAndRender(
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing elitr</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>'],
            ['<p>Lorem ipsum dolor sit amet</p>', '<p>Consetetur sadipscing elitr</p>']
        );

        // Deleted by the parent, un-deleted by the child
        $this->assertSame(
            '<p class="deletedOuter inserted">Consetetur sadipscing elitr</p>',
            $rendered[1]
        );
    }
}
