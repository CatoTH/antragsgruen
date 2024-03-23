<?php

namespace Tests\Unit;

use app\models\sectionTypes\TextSimpleCommon;
use Tests\Support\Helper\TestBase;

class StripAfterInsertedNewslinesTest extends TestBase
{
    public function testShouldStrip1(): void
    {
        $html = '<p>###LINENUMBER###Some unchanged text. <ins>Some inserted text.<br></ins>Some more unchanged </p>';
        $out = TextSimpleCommon::stripAfterInsertedNewlines($html);
        $expected = '<p>###LINENUMBER###Some unchanged text. <ins>Some inserted text.</ins></p>';
        $this->assertEquals($expected, $out);
    }

    public function testShouldNotStrip1(): void
    {
        $html = '<p>###LINENUMBER###<del>mö</del><ins>je</ins>gliche Diskriminierung durch Algorithmen</p>';
        $out = TextSimpleCommon::stripAfterInsertedNewlines($html);
        $expected = '<p>###LINENUMBER###<del>mö</del><ins>je</ins>gliche Diskriminierung durch Algorithmen</p>';
        $this->assertEquals($expected, $out);
    }
}
