<?php

namespace unit;

use app\components\diff\Diff;
use Codeception\Specify;
use Codeception\Util\Autoload;

Autoload::addNamespace('unit', __DIR__);

class DiffTest extends TestBase
{
    use Specify;

    /**
     *
     */
    public function testParagraphs()
    {
        $this->specify(
            'Diff Test 1',
            function () {
                $str1   = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>' . "\n" . '<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';
                $str2   = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift '
                    . 'vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachsdfsdfsdf '
                    . 'helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln '
                    . 'do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';
                $expect = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa <del>Oachkatzlschwoaf hod Wiesn.</del></p>' . "\n" .
                    '<p>Oa<del>moi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds</del><ins>chsdfsdfsdf</ins> helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn.</p>';

                $diff = new Diff();
                $out  = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Diff Test 2',
            function () {
                $str1   = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4 Str5';
                $str2   = 'Str1 Str2 Str3 Str4';
                $expect = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4<del> Str5</del>';

                $diff = new Diff();
                $diff->setIgnoreStr('###LINEBREAK###');
                $out = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Diff Test 3',
            function () {
                $str1   = 'Abcdef abcdef Abcdef AAbcdef Abcdef';
                $str2   = 'Abcdef abcdefghi Abcdef AAbcdef preAbcdef';
                $expect = 'Abcdef abcdef<ins>ghi</ins> Abcdef AAbcdef <ins>pre</ins>Abcdef';

                $diff = new Diff();
                $out  = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Diff Test 4',
            function () {
                $str1   = 'ym Bla gagen lerd mal';
                $str2   = 'ym Blagagen lerd mal';
                $expect = 'ym Bla<del> </del>gagen lerd mal';

                $diff = new Diff();
                $out  = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Diff Test 5',
            function () {
                $str1   = 'uns dann als Zeichen das sie uns überwunden hatten';
                $str2   = 'uns dann als Zeichen, dass sie uns überwunden hatten';
                $expect = 'uns dann als Zeichen<del> da</del><ins>, das</ins>s sie uns überwunden hatten';

                $diff = new Diff();
                $out  = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Diff Test 6',
            function () {
                $str1   = 'Test <strong>Test1</strong> Test2';
                $str2   = 'Test <strong>Test2</strong> Test2';
                $expect = 'Test <strong>Test<del>1</del><ins>2</ins></strong> Test2';

                $diff = new Diff();
                $out  = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );
    }
}
