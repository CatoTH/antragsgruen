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
        $expect = '<p>I mechad dee Schwoanshaxn ghupft wia gsprunga measi gschmeidig hawadere midananda vui huift vui Biawambn, des wiad a Mordsgaudi is. Biaschlegl soi oans, zwoa, gsuffa <del>Oachkatzlschwoaf hod Wiesn.</del></p>' . "<del>\n</del>" .
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


        $str1   = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird Und noch etwas am Ende. Und noch etwas am Ende</p>';
        $str2   = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen Und noch etwas am Ende. Und noch etwas am Ende</p>';
        $expect = '<p>Demokratie und Freiheit gehören untrennbar zusammen. Wir haben einen partizipativen Freiheitsbegriff. Demokratie ist der Rahmen für die Freiheit sich zu beteiligen, mitzugestalten und zu entscheiden. Erweiterte demokratische Mitwirkungsmöglichkeiten von BürgerInnen in einer vitalen Demokratie bedeuten einen Zugewinn an Freiheit. Demokratie lebt von den Beiträgen und dem ständigen Abwägungsprozess einer lebendigen Zivilgesellschaft. Immer wieder wird es demokratische Entscheidungen geben, die uns nicht gefallen. Freiheit ist aber immer und vor allem die Freiheit der Andersdenkenden. Wir setzen uns für mehr direkte Demokratie und gegen die negativen Auswirkungen wirtschaftlicher Macht und intransparenter Entscheidungsprozesse auf Freiheit ein. So kann eine aktive und selbstbestimmte BürgerInnengesellschaft eigene Entscheidungen treffen. <del>Auch werden wir demokratische Strukturen und Entscheidungsmechanismen verteidigen. Gerade in Zeiten der Globalisierung ist ein besseres Europa die Antwort auf die Sicherung von Freiheit. Die EU kann das Primat der Politik sichern, wenn sie den aus dem Ruder gelaufenen Wirtschaftsliberalismus einhegt und nicht über Geheimverträge wie ACTA oder TTIP voranbringen will. Die Freiheitsrechte der Bürgerinnen und Bürger werden aber dann tangiert, wenn der sie schützende Rechtsrahmen durch internationale Abkommen unterminiert wird Und noch etwas am Ende.</del>
<ins>Eine Politische Ökonomie kann demokratisch und grundrechtsorientiert betrieben werden. Diese Möglichkeit bieten die gemischten Wirtschaften in Europa und diese Möglichkeit wollen wir sichern und ausbauen. Geheimverträge wie ACTA und TTIP schränken diese Fähigkeit ein. Die Rechte der ArbeitnehmerInnen und VerbraucherInnen werden nicht gestärkt, sondern abgebaut. Nicht einmal die Einhaltung der ILO-Abkommen wird gefordert. Internationale Abkommen sollen die Möglichkeit bieten, Grundrechte zu stärken, nicht diese Fähigkeit in den Vertragsstaaten künftig verunmöglichen Und noch etwas am Ende.</ins> Und noch etwas am Ende</p>';

        $diff = new Diff();
        $out  = $diff->computeDiff($str1, $str2);
        $out  = $diff->cleanupDiffProblems($out);

        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testShiftMisplacedTags()
    {
        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['Newly ', Engine::INSERTED],
            ['inserted ', Engine::INSERTED],
            ['normal ', Engine::UNMODIFIED],
            [' text', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertEquals($orig, $corrected);



        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['Old ', Engine::DELETED],
            ['deleted ', Engine::DELETED],
            ['normal ', Engine::UNMODIFIED],
            [' text', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertEquals($orig, $corrected);



        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['New ', Engine::INSERTED],
            ['content', Engine::INSERTED],
            ['</p>', Engine::INSERTED],
            ['', Engine::INSERTED],
            ['<p>', Engine::INSERTED],
            ['more', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertEquals([
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::INSERTED],
            ['New ', Engine::INSERTED],
            ['content', Engine::INSERTED],
            ['</p>', Engine::INSERTED],
            ['', Engine::INSERTED],
            ['<p>', Engine::UNMODIFIED],
            ['more', Engine::UNMODIFIED],
        ], $corrected);


        $orig      = [
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::UNMODIFIED],
            ['Deleted ', Engine::DELETED],
            ['content', Engine::DELETED],
            ['</p>', Engine::DELETED],
            ['', Engine::DELETED],
            ['<p>', Engine::DELETED],
            ['more', Engine::UNMODIFIED],
        ];
        $corrected = Engine::shiftMisplacedHTMLTags($orig);
        $this->assertEquals([
            ['', Engine::UNMODIFIED],
            ['<p>', Engine::DELETED],
            ['Deleted ', Engine::DELETED],
            ['content', Engine::DELETED],
            ['</p>', Engine::DELETED],
            ['', Engine::DELETED],
            ['<p>', Engine::UNMODIFIED],
            ['more', Engine::UNMODIFIED],
        ], $corrected);
    }
}
