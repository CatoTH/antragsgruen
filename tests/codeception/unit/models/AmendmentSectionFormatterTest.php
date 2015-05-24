<?php

namespace app\tests\codeception\unit\models;

use app\components\diff\AmendmentSectionFormatter;
use Codeception\Specify;
use yii\codeception\TestCase;

class AmendmentSectionFormatterTest extends TestCase
{
    use Specify;

    /**
     *
     */
    public function testUlLi()
    {
        $this->specify(
            'Inserted LIs should not be shown',
            function () {
                $in     = ['<ul class="inserted"><li>Oamoi a Maß und no a Maß</li></ul>'];
                $expect = ['<ul class="inserted"><li>Oamoi a Maß und no a Maß</li></ul>'];
                $out    = AmendmentSectionFormatter::getDiffLinesWithNumberComputed($in, 0, false);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Line breaks within lists',
            function () {
                $in = '<p>###LINENUMBER###Do nackata Wurscht i hob di ' .
                    '###LINENUMBER###narrisch gean, Diandldrahn Deandlgwand vui ' .
                    '###LINENUMBER###Do nackata</p>' . "\n" .
                    '<ul><li>###LINENUMBER###Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand ###LINENUMBER###huift vui woaß?</li></ul>' . "\n" .
                    '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>';

                $expect = [
                    '###LINENUMBER###Do nackata Wurscht i hob di ',
                    '###LINENUMBER###narrisch gean, Diandldrahn Deandlgwand vui ',
                    '###LINENUMBER###Do nackata',
                    '<ul><li>###LINENUMBER###Do nackata Wurscht i hob di narrisch gean, Diandldrahn Deandlgwand ###LINENUMBER###huift vui woaß?</li></ul>',
                    '<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>'
                ];

                $out = AmendmentSectionFormatter::getDiffSplitToLines($in);

                $this->assertEquals($expect, $out);
            }
        );

        // @TODO:
        // - <li>s with multiple lines
        // - <li>s that are changed
        // - <li>s that are deleted

    }
}
