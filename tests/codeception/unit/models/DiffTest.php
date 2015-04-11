<?php

namespace app\tests\codeception\unit\models;

use app\components\diff\Diff;
use Codeception\Specify;
use yii\codeception\TestCase;

class DiffTest extends TestCase
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
                $str1 = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachkatzlschwoaf hod Wiesn.</p>' . "\n" . '<p>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht’n Tag schuf Gott des Bia i sog ja nix, i red ja bloß jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf’n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl Fünferl Gams.</p>';
                $str2 = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. '
                    . 'Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui '
                    . 'lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. '
                    . 'I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift '
                    . 'vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa Oachsdfsdfsdf '
                    . 'helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln '
                    . 'do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz '
                    . 'kimmt, um Godds wujn. Am acht’n Tag schuf Gott des Bia i sog ja nix, i red ja bloß jedza, '
                    . 'Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf’n Gipfe Servas des wiad a Mordsgaudi, '
                    . 'griasd eich midnand Bladl Fünferl Gams.</p>';
                $expect = '<p>Woibbadinga damischa owe gwihss Sauwedda ded Charivari dei heid gfoids ma sagrisch guad. Maßkruag wo hi mim Radl foahn Landla Leonhardifahrt, Radler. Ohrwaschl und glei wirds no fui lustiga Spotzerl Fünferl, so auf gehds beim Schichtl do legst di nieda ned Biawambn Breihaus. I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa <del>Oachkatzlschwoaf hod Wiesn.</del></p>' . "\n" . '<p><del>Oamoi großherzig Mamalad, liberalitas Bavariae hoggd! Nimmds</del><ins>Oachsdfsdfsdf</ins> helfgod im Beidl des basd scho i hob di liab. A Prosit der Gmiadlichkeit midanand mim obandln do mim Radl foahn, Jodler. Ned woar Brotzeit Brotzeit gwihss eana Gidarn. Foidweg Spuiratz kimmt, um Godds wujn. Am acht’n Tag schuf Gott des Bia i sog ja nix, i red ja bloß jedza, Biakriagal a bissal wos gehd ollaweil. Ledahosn om auf’n Gipfe Servas des wiad a Mordsgaudi, griasd eich midnand Bladl Fünferl Gams.</p>';

                $diff = new Diff();
                $out = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Diff Test 2',
            function () {
                $str1 = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4 Str5';
                $str2 = 'Str1 Str2 Str3 Str4';
                $expect = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4 <del>Str5</del>';

                $diff = new Diff();
                $diff->setIgnoreStr('###LINEBREAK###');
                $out = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );



        $this->specify(
            'Diff Test 3',
            function () {
                $str1 = 'Abcdef abcdef Abcdef AAbcdef Abcdef';
                $str2 = 'Abcdef abcdefghi Abcdef AAbcdef preAbcdef';
                $expect = 'Abcdef abcdef<ins>ghi</ins> Abcdef AAbcdef <ins>pre</ins>Abcdef';

                $diff = new Diff();
                $out = $diff->computeDiff($str1, $str2);

                $this->assertEquals($expect, $out);
            }
        );

    }
}
