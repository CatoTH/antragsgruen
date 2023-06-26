<?php

namespace unit;

use app\models\sectionTypes\TextSimple;
use Codeception\Specify;

class StripAfterInsertedNewslinesTest extends TestBase
{
    use Specify;

    /**
     */
    public function testShouldStrip1()
    {
        $html = '<p>###LINENUMBER###Some unchanged text. <ins>Some inserted text.<br></ins>Some more unchanged </p>';
        $out = TextSimple::stripAfterInsertedNewlines($html);
        $expected = '<p>###LINENUMBER###Some unchanged text. <ins>Some inserted text.</ins></p>';
        $this->assertEquals($expected, $out);
    }

    public function testShouldNotStrip1()
    {
        $html = '<p>###LINENUMBER###<del>mö</del><ins>je</ins>gliche Diskriminierung durch Algorithmen</p>';
        $out = TextSimple::stripAfterInsertedNewlines($html);
        $expected = '<p>###LINENUMBER###<del>mö</del><ins>je</ins>gliche Diskriminierung durch Algorithmen</p>';
        $this->assertEquals($expected, $out);
    }
}
