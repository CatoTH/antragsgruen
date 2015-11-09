<?php

namespace unit;

use app\components\diff\DiffRenderer;
use app\components\HTMLTools;
use Codeception\Specify;

class DiffRendererTest extends TestBase
{
    use Specify;

    /**
     */
    public function testSplitText()
    {
        $renderer = new DiffRenderer();
        list($nodes, $inIns, $inDel) = $renderer->textToNodes(
            'Test1###INS_START###Inserted###INS_END###Bla###INS_START###Inserted###INS_END###Bla###DEL_START###Deleted###DEL_END###Ende',
            false,
            false
        );
        $this->assertEquals(7, count($nodes));
        $this->assertEquals('ins', $nodes[1]->nodeName);
        $this->assertEquals('Inserted', $nodes[1]->childNodes[0]->nodeValue);
        $this->assertEquals('Bla', $nodes[2]->nodeValue);
        $this->assertEquals('ins', $nodes[3]->nodeName);
        $this->assertEquals('del', $nodes[5]->nodeName);
        $this->assertEquals('Ende', $nodes[6]->nodeValue);
        $this->assertEquals(false, $inIns);
        $this->assertEquals(false, $inDel);

        $renderer = new DiffRenderer();
        list($nodes, $inIns, $inDel) = $renderer->textToNodes(
            'Test1###INS_START###Inserted###INS_END###Bla###INS_START###Inserted###INS_END###Bla###DEL_START###Deleted',
            true,
            false
        );

        $this->assertEquals(5, count($nodes));
        $this->assertEquals('ins', $nodes[0]->nodeName);
        $this->assertEquals('Test1###INS_START###Inserted', $nodes[0]->childNodes[0]->nodeValue);
        $this->assertEquals('Bla', $nodes[1]->nodeValue);
        $this->assertEquals('del', $nodes[4]->nodeName);
        $this->assertEquals(false, $inIns);
        $this->assertEquals(true, $inDel);
    }

    /**
     */
    public function testLevel1()
    {
        $renderer = new DiffRenderer();

        $html = '<p>Test 1 ###INS_START###Inserted ###INS_END###Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <ins>Inserted </ins>Test 2</p>', $rendered);

        $html = '<p>Test 1 ###INS_START###Inserted Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <ins>Inserted Test 2</ins></p>', $rendered);

        $html = '<p>Test 1 ###DEL_START###Deleted ###DEL_END###Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <del>Deleted </del>Test 2</p>', $rendered);

        $html = '<p>Test 1 ###DEL_START###Deleted Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <del>Deleted Test 2</del></p>', $rendered);
    }

    /**
     */
    public function testLevel2()
    {
        $renderer = new DiffRenderer();

        $html = '<p>Test 1 <strong>Fett</strong> ###DEL_START###Deleted Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <strong>Fett</strong> <del>Deleted Test 2</del></p>', $rendered);

        $html = '<p>Test 1 ###DEL_START###Deleted <strong>Fett</strong> Test 2</p>';
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        $this->assertEquals('<p>Test 1 <del>Deleted <strong>Fett</strong> Test 2</del></p>', $rendered);
    }

    /**
     */
    public function testStd()
    {
        return;
        $html     = '<p>Test 123<strong> Alt###INS_START###</strong> Neu normal<strong>Neu fett ###INS_END###alt</strong> Ende</p>';
        $renderer = new DiffRenderer();
        $rendered = $renderer->renderHtmlWithPlaceholders($html);
        HTMLTools::printDomDebug($rendered);
    }
}
