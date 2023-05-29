<?php

namespace Tests\Unit;

use app\components\diff\AmendmentRewriter;
use Tests\Support\Helper\TestBase;

class AmendmentRewriterPerformTest extends TestBase
{
    /**
     */
    public function testBasic1(): void
    {
        $oldHtml       = '<p>Test 123 <strong>STRONG</strong></p>' . '<p>Test 4</p>';
        $amendmentHtml = '<p>Test 456 <STRONG>STRONG</STRONG></p>' . '<p>Test 4</p>';
        $newHtml       = '<p>Test 123 <STRONG>STRONG</STRONG></p>' . '<p>Test 5</p>';

        $rewritten = AmendmentRewriter::performRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertEquals('<p>Test 456 <strong>STRONG</strong></p>' . '<p>Test 5</p>', $rewritten);
    }

    /**
     */
    public function testBasic2(): void
    {
        $oldHtml       = '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul>';
        $amendmentHtml = '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?</li></ul><ul><li>Neuer Punkt</li></ul>';
        $newHtml       = '<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?123</li></ul>';

        $rewritten = AmendmentRewriter::performRewrite($oldHtml, $newHtml, $amendmentHtml);
        $this->assertEquals('<ul><li>Auffi Gamsbart nimma de Sepp Ledahosn Ohrwaschl um Godds wujn Wiesn Deandlgwand Mongdratzal! Jo leck mi Mamalad i daad mechad?123</li><li>Neuer Punkt</li></ul>', $rewritten);
    }
}
