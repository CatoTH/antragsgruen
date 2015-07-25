<?php

namespace unit;

use app\components\diff\Diff;
use app\components\diff\Engine;
use Codeception\Specify;

class DiffTest extends TestBase
{
    use Specify;

    /**
     */
    public function testParagraphs()
    {
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


        $str1   = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4 Str5';
        $str2   = 'Str1 Str2 Str3 Str4';
        $expect = '###LINEBREAK###Str1 Str2 Str3###LINEBREAK### Str4<del> Str5</del>';

        $diff = new Diff();
        $diff->setIgnoreStr('###LINEBREAK###');
        $out = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'Abcdef abcdef Abcdef AAbcdef Abcdef';
        $str2   = 'Abcdef abcdefghi Abcdef AAbcdef preAbcdef';
        $expect = 'Abcdef abcdef<ins>ghi</ins> Abcdef AAbcdef <ins>pre</ins>Abcdef';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'ym Bla gagen lerd mal';
        $str2   = 'ym Blagagen lerd mal';
        $expect = 'ym Bla<del> </del>gagen lerd mal';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'uns dann als Zeichen das sie uns überwunden hatten';
        $str2   = 'uns dann als Zeichen, dass sie uns überwunden hatten';
        $expect = 'uns dann als Zeichen<del> da</del><ins>, das</ins>s sie uns überwunden hatten';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);


        $str1   = 'Test <strong>Test1</strong> Test2';
        $str2   = 'Test <strong>Test2</strong> Test2';
        $expect = 'Test <strong>Test<del>1</del><ins>2</ins></strong> Test2';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);

        $this->assertEquals($expect, $out);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testTwoInserts()
    {
        $str1   = '<ul><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<p>###LINENUMBER###I waar soweid Blosmusi es nomoi.</p>';
        $str2   = '<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<ul><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>
<ul><li>Blabla</li></ul>
<p>I waar soweid Blosmusi es nomoi.</p>';
        $expect = '<ul><li>###LINENUMBER###Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>
<ul class="inserted"><li>Oamoi a Maß und no a Maß des basd scho wann griagd ma nacha wos z’dringa do Meidromml, oba a fescha Bua!</li></ul>
<ul class="inserted"><li>Blabla</li></ul>
<p>###LINENUMBER###I waar soweid Blosmusi es nomoi.</p>';

        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $diff->setFormatting(0);
        $out = $diff->computeDiff($str1, $str2);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testGroupOperations()
    {
        $src     = [
            [
                '<p>###LINENUMBER###Wui helfgod Wiesn. Sauwedda an Brezn, abfieseln.</p>',
                Engine::DELETED
            ],
            [
                '<p>###LINENUMBER###Wui helfgod Wiesn.</p>',
                Engine::INSERTED
            ],
            [
                '',
                Engine::UNMODIFIED
            ]
        ];
        $diff    = new Diff();
        $grouped = $diff->groupOperations($src, Diff::ORIG_LINEBREAK);
        $this->assertEquals($src, $grouped); // Should not be changed
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testTwoChangedLis()
    {
        $str1   = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.</li></ul>
<ul><li>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';
        $str2   = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.asdasd</li></ul>
<ul><li>aWoibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';
        $expect = '<ul><li>Test123</li></ul>
<ul><li>Ned Mamalad auffi i bin a woschechta Bayer greaßt eich nachad, umananda gwiss nia need Weiznglasl.<ins>asdasd</ins></li></ul>
<ul><li><ins>a</ins>Woibbadinga noch da Giasinga Heiwog Biazelt mechad mim Spuiratz, soi zwoa.</li></ul>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $this->assertEquals($expect, $out);
    }

    /**
     * @throws \app\models\exceptions\Internal
     */
    public function testReplaceParagraph()
    {
        $str1   = '<p>Unchanging line</p>
<p>Das wollen wir mit unserer Zeitpolitik ermöglichen. Doch wie die Aufgaben innerhalb der Familie verteilt werden, ' .
            'entscheidet sich heute oft in ernüchternder Weise: Selbst wenn Paare gleichberechtigt und in ' .
            'gegenseitigem Einvernehmen die Rollenverteilung miteinander ausmachen wollen, scheitern sie zu oft ' .
            'an der Realität – und leben plötzlich Rollenbilder, die sie eigentlich so nie wollten. ' .
            'Verkrustete Strukturen und Fehlanreize regieren in ihr Leben hinein; sie verhindern, dass Frauen und ' .
            'Männer selbstbestimmt und auf Augenhöhe ihre Entscheidungen treffen können.</p>';
        $str2   = '<p>Unchanging line</p>
<p>Diesen Wunsch der Paare in die Realität umzusetzen ist das Ziel unserer Zeitpolitik. Hierfür sind verkrustete ' .
            'patriarchalische Strukturen und Fehlanreize abzubauen, jedoch ohne dass neuer sozialer Druck auf ' .
            'Familien entsteht. Damit Paare selbstbestimmt und auf Augenhöhe die Rollenverteilung in ihrer Familie ' .
            'festlegen können, muss die Gesellschaft die Entscheidungen der Familien unabhängig von ihrem Ergebnis ' .
            'akzeptieren und darf keine Lebensmodelle stigmatisieren.</p>';
        $expect = '<p>Unchanging line</p>
<p><del>Das wollen wir mit unserer Zeitpolitik ermöglichen. Doch wie die Aufgaben innerhalb der Familie verteilt werden, entscheidet sich heute oft in ernüchternder Weise: Selbst wenn Paare gleichberechtigt und in gegenseitigem Einvernehmen die Rollenverteilung miteinander ausmachen wollen, scheitern sie zu oft an der Realität – und leben plötzlich Rollenbilder, die sie eigentlich so nie wollten. Verkrustete Strukturen und Fehlanreize regieren in ihr Leben hinein; sie verhindern, dass Frauen und Männer selbstbestimmt und auf Augenhöhe ihre Entscheidungen treffen können.</del></p>
<p><ins>Diesen Wunsch der Paare in die Realität umzusetzen ist das Ziel unserer Zeitpolitik. Hierfür sind verkrustete patriarchalische Strukturen und Fehlanreize abzubauen, jedoch ohne dass neuer sozialer Druck auf Familien entsteht. Damit Paare selbstbestimmt und auf Augenhöhe die Rollenverteilung in ihrer Familie festlegen können, muss die Gesellschaft die Entscheidungen der Familien unabhängig von ihrem Ergebnis akzeptieren und darf keine Lebensmodelle stigmatisieren.</ins></p>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $out  = $diff->cleanupDiffProblems($out);

        $this->assertEquals($expect, $out);
    }
}
