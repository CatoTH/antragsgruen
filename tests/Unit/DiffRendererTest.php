<?php

namespace Tests\Unit;

use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use Codeception\Attribute\Incomplete;
use Codeception\Attribute\Skip;
use Tests\Support\Helper\TestBase;

class DiffRendererTest extends TestBase
{
    public function testNodeToText(): void
    {
        $html = '<div> 1 <em>2 <strike>###LINENUMBER### 2</strike></em> 3 <span>4</span>';
        $dom = HTMLTools::html2DOM($html);
        $text = DiffRenderer::nodeToPlainText($dom);
        $this->assertEquals('1 2 2 3 4', $text);
    }

    public function testAria1(): void
    {
        $renderer = new DiffRenderer();
        $renderer->setFormatting(DiffRenderer::FORMATTING_CLASSES_ARIA);

        $html     = '<p>Test###INS_START###Neuer Absatz###INS_END### ###DEL_START###Neuer Absatz###DEL_END###.</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test<ins aria-label="Einfügen: „Neuer Absatz”">Neuer Absatz</ins> <del aria-label="Streichen: „Neuer Absatz”">Neuer Absatz</del>.</p>', $rendered);
    }

    public function testAria2(): void
    {
        $renderer = new DiffRenderer();
        $renderer->setFormatting(DiffRenderer::FORMATTING_CLASSES_ARIA);

        $html     = '<ul><li>Test###INS_START###<p>Neuer Absatz</p>###INS_END###.</li></ul>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<ul><li>Test<p class="inserted" aria-label="Einfügen: „Neuer Absatz”">Neuer Absatz</p>.</li></ul>', $rendered);
    }

    public function testCallback(): void
    {
        $renderer = new DiffRenderer();
        $renderer->setInsCallback(function ($node, $params) use (&$cbParam) {
            /** @var \DOMElement $node */
            $cbParam = $params;
            $node->setAttribute('test', '1');
        });
        $renderer->setDelCallback(function ($node, $params) use (&$cbParam) {
            /** @var \DOMElement $node */
            $cbParam = $params;
            $node->setAttribute('test', '2');
        });


        $str      = 'Test 123 ###INS_START0-2### kjhkjh';
        $cbParam  = null;
        $rendered = $renderer->renderHtmlWithPlaceholders($str);
        $this->assertEquals('0-2', $cbParam);
        $this->assertEquals('Test 123 <ins test="1"> kjhkjh</ins>', $rendered);


        $str      = 'Test 123 ###DEL_START0-2### kjhkjh';
        $cbParam  = null;
        $rendered = $renderer->renderHtmlWithPlaceholders($str);
        $this->assertEquals('0-2', $cbParam);
        $this->assertEquals('Test 123 <del test="2"> kjhkjh</del>', $rendered);


        $str      = '###DEL_START0-2### kjhkjh <ul><li>List</li></ul>###DEL_END### Ende';
        $cbParam  = null;
        $rendered = $renderer->renderHtmlWithPlaceholders($str);
        $this->assertEquals('0-2', $cbParam);
        $this->assertEquals('<del test="2"> kjhkjh </del><ul class="deleted" test="2"><li>List</li></ul> Ende', $rendered);


        $str      = '###INS_START0-2### kjhkjh <ul><li>List</li></ul>###INS_END### Ende';
        $cbParam  = null;
        $rendered = $renderer->renderHtmlWithPlaceholders($str);
        $this->assertEquals('0-2', $cbParam);
        $this->assertEquals('<ins test="1"> kjhkjh </ins><ul class="inserted" test="1"><li>List</li></ul> Ende', $rendered);
    }

    public function testSplitText(): void
    {
        $renderer = new DiffRenderer();
        list($nodes, $inIns, $inDel) = $renderer->textToNodes(
            'Test1###INS_START###Inserted###INS_END###Bla###INS_START###Inserted###INS_END###Bla###DEL_START###Deleted###DEL_END###Ende',
            null,
            null,
            null
        );
        $this->assertCount(7, $nodes);
        $this->assertEquals('ins', $nodes[1]->nodeName);
        $this->assertEquals('Inserted', $nodes[1]->childNodes[0]->nodeValue);
        $this->assertEquals('Bla', $nodes[2]->nodeValue);
        $this->assertEquals('ins', $nodes[3]->nodeName);
        $this->assertEquals('del', $nodes[5]->nodeName);
        $this->assertEquals('Ende', $nodes[6]->nodeValue);
        $this->assertNull($inIns);
        $this->assertNull($inDel);

        $renderer = new DiffRenderer();
        list($nodes, $inIns, $inDel) = $renderer->textToNodes(
            'Test1###INS_START###Inserted###INS_END###Bla###INS_START###Inserted###INS_END###Bla###DEL_START###Deleted',
            '',
            null,
            null
        );

        $this->assertCount(5, $nodes);
        $this->assertEquals('ins', $nodes[0]->nodeName);
        $this->assertEquals('Test1###INS_START###Inserted', $nodes[0]->childNodes[0]->nodeValue);
        $this->assertEquals('Bla', $nodes[1]->nodeValue);
        $this->assertEquals('del', $nodes[4]->nodeName);
        $this->assertNull($inIns);
        $this->assertEquals('', $inDel);
    }

    public function testLevel1(): void
    {
        $renderer = new DiffRenderer();

        $html     = '<p>Test 1 ###INS_START###Inserted ###INS_END###Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <ins>Inserted </ins>Test 2</p>', $rendered);

        $html     = '<p>Test 1 ###INS_START###Inserted Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <ins>Inserted Test 2</ins></p>', $rendered);

        $html     = '<p>Test 1 ###DEL_START###Deleted ###DEL_END###Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <del>Deleted </del>Test 2</p>', $rendered);

        $html     = '<p>Test 1 ###DEL_START###Deleted Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <del>Deleted Test 2</del></p>', $rendered);
    }

    public function testLevel2(): void
    {
        $renderer = new DiffRenderer();

        $html     = '<p>Test 1 <strong>Fett</strong> ###DEL_START###Deleted Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <strong>Fett</strong> <del>Deleted Test 2</del></p>', $rendered);

        $html     = '<p>Test 1 ###DEL_START###Deleted <strong>Fett</strong> Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <del>Deleted <strong>Fett</strong> Test 2</del></p>', $rendered);

        $html     = '<p>Test 1 ###INS_START###Inserted <strong>Fett</strong> Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <ins>Inserted <strong>Fett</strong> Test 2</ins></p>', $rendered);

        $html     = '<ul><li>Test 1###INS_START###</li><li>Neuer Punkt</li><li>###INS_END###Test 2</li></ul>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<ul><li>Test 1</li><li class="inserted">Neuer Punkt</li><li>Test 2</li></ul>', $rendered);

        $html     = '<ul><li>Test 1###DEL_START###</li><li>Gelöschter Punkt</li><li>###DEL_END###Test 2</li></ul>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<ul><li>Test 1</li><li class="deleted">Gelöschter Punkt</li><li>Test 2</li></ul>', $rendered);

        $html     = '<ul><li>Test 1###INS_START###23</li><li>Neuer Punkt</li><li>Start###INS_END###Test 2</li></ul>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<ul><li>Test 1<ins>23</ins></li><li class="inserted">Neuer Punkt</li><li><ins>Start</ins>Test 2</li></ul>', $rendered);

        $html     = '<p>Test 123<strong> Alt###INS_START###</strong> Neu normal<strong>Neu fett ###INS_END###alt</strong> Ende</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 123<strong> Alt</strong><ins> Neu normal</ins><strong><ins>Neu fett </ins>alt</strong> Ende</p>', $rendered);
    }

    public function testLevel3(): void
    {
        $renderer = new DiffRenderer();

        $html     = '<ul><li>Test###INS_START###<p>Neuer Absatz</p>###INS_END###.</li></ul>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<ul><li>Test<p class="inserted">Neuer Absatz</p>.</li></ul>', $rendered);
    }

    #[Incomplete('TODO')]
    public function testInsertedListElement(): void
    {
        $renderer = new DiffRenderer();

        $html     = '<ul><li>###LINENUMBER###Nested 1###INS_START###</li><li>Nested <strong>2</strong>###INS_END###</li></ul>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<ul><li>###LINENUMBER###Nested 1</li><li class="inserted">Nested <strong>2</strong></li></ul>', $rendered);
    }

    #[Incomplete('TODO')]
    public function testChangedOlNumbering(): void
    {
        $renderer = new DiffRenderer();
        $html     = '###DEL_START###<ol start="2">###DEL_END######INS_START###<ol start="1">###INS_END###<li>Test 123</li></ol>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('', $rendered);
    }

    public function testParagraphContainsDiff(): void
    {
        $str = 'Test<ins class="irgendwas">Bla';
        $this->assertEquals(4, DiffRenderer::paragraphContainsDiff($str));

        $str = 'Test ä<ins class="irgendwas">Bla';
        $this->assertEquals(6, DiffRenderer::paragraphContainsDiff($str));

        $str = 'Test<inserted class="irgendwas">Bla';
        $this->assertNull(DiffRenderer::paragraphContainsDiff($str));

        $str = 'Test</ins>';
        $this->assertNull(DiffRenderer::paragraphContainsDiff($str));

        $str = '<pre class="inserted">Blabla';
        $this->assertEquals(0, DiffRenderer::paragraphContainsDiff($str));

        $str = '<pre class="x inserted bold">Blabla';
        $this->assertEquals(0, DiffRenderer::paragraphContainsDiff($str));

        $str = '<pre class="insertedbold">Blabla';
        $this->assertEquals(0, DiffRenderer::paragraphContainsDiff($str));

        $str = '<pre> class="inserted" Blabla';
        $this->assertEquals(0, DiffRenderer::paragraphContainsDiff($str));
    }

    public function testKeepMovedParagraphMarkup(): void
    {
        $renderer = new DiffRenderer();

        $html     = '###INS_START###<p data-moving-partner-id="1" data-moving-partner-paragraph="3" class="moved">Test 1 Test 2</p>###INS_END###';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p class="moved inserted" data-moving-partner-id="1" data-moving-partner-paragraph="3">Test 1 Test 2</p>', $rendered);
    }
}
