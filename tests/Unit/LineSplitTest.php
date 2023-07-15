<?php

namespace Tests\Unit;

use app\components\LineSplitter;
use Tests\Support\Helper\TestBase;

class LineSplitTest extends TestBase
{
    public function testWorkAfterManualLineBreaks(): void
    {
        $orig1 = '<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invi dun tutlabore</p>';
        $expect1 = [
            "<p>Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invi dun ",
            "tutlabore</p>",
        ];

        $orig2 = '<p><strong>Eine Zeile zuvor</strong><br>' . "\n" .
            "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invi dun tutlabore</p>";
        $expect2 = [
            '<p><strong>Eine Zeile zuvor</strong><br>' . "\n",
            "Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invi dun ",
            "tutlabore</p>",
        ];

        $out = LineSplitter::splitHtmlToLines($orig1, 95, '');
        $this->assertSame($expect1, $out);

        $out = LineSplitter::splitHtmlToLines($orig2, 95, '');
        $this->assertSame($expect2, $out);
    }

    public function testCase1(): void
    {
        $orig   = "Geschäftsordnung der Bundesversammlung geregelt. " .
            "Antragsberechtigt sind die Orts- und Kreisverbände, die " .
            "Landesversammlungen bzw. Landesdelegiertenkonferenzen,";
        $expect = [
            "Geschäftsordnung der Bundesversammlung geregelt. Antragsberechtigt sind die ",
            "Orts- und Kreisverbände, die Landesversammlungen bzw. ",
            "Landesdelegiertenkonferenzen,"
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '');
        $this->assertSame($expect, $out);
    }

    public function testCase2(): void
    {
        $orig   = "gut und richtig, wenn Eltern selbst eine Initiative für " .
            "Kinderbetreuung gründen – besser ist";
        $expect = [
            "gut und richtig, wenn Eltern selbst eine Initiative für Kinderbetreuung gründen ",
            "– besser ist"
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '');
        $this->assertSame($expect, $out);
    }

    public function testCase3(): void
    {
        $orig   = "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, " .
            "wenn sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. " .
            "Es ist gut und richtig,";
        $expect = [
            "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, wenn ",
            "sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. Es ist ",
            "gut und richtig,"
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '');
        $this->assertSame($expect, $out);
    }

    public function testCase4(): void
    {
        $orig   = "angehen, ist von gro&szlig;em Wert f&uuml;r unser Land. Ver&auml;nderung " .
            "kann nur gelingen, wenn sie von Vielen ";
        $expect = [
            "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, wenn ",
            "sie von Vielen "
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '');
        $this->assertSame($expect, $out);
    }

    public function testCase5(): void
    {
        $orig   = "1angehen, ist von<br>gro&szlig;em Wert f&uuml;r<br>\nunser Land. Ver&auml;nderung " .
            "kann nur gelingen, wenn sie von Vielen sdfsdf sdfsdsdf dfdfs sf d";
        $expect = [
            "1angehen, ist von<br>",
            "großem Wert für<br>\n",
            "unser Land. Veränderung kann nur gelingen, wenn sie von Vielen sdfsdf sdfsdsdf ",
            "dfdfs sf d"
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '');
        $this->assertSame($expect, $out);
    }

    public function testCaseGrapheme(): void
    {
        $orig   = "🫶🏻🫶🏼🫶🏽🫶🏾🫶🏿🫶🏻🫶🏼🫶🏽🫶🏾🫶🏿🫶🏻🫶🏼🫶🏽🫶🏾🫶🏿";
        $expect = [
            "🫶🏻🫶🏼🫶🏽🫶🏾🫶🏿-",
            "🫶🏻🫶🏼🫶🏽🫶🏾🫶🏿-",
            "🫶🏻🫶🏼🫶🏽🫶🏾🫶🏿",
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 5, '');
        $this->assertSame($expect, $out);
    }

    public function testWithDashInWord(): void
    {
        $orig   = '<p>nationalen Parlamente sowie die Rückkehr zur Gemeinschaftsmethode und eine EU-Kommissarin oder einen EU-Kommissar; er oder sie soll der Eurogruppe vorsitzen und mit allen WWU-relevanten Kompetenzen ausgestattet sein.</p>';
        $expect = [
            '<p>nationalen Parlamente sowie die Rückkehr zur Gemeinschaftsmethode und eine EU-Kommissarin ',
            'oder einen EU-Kommissar; er oder sie soll der Eurogruppe vorsitzen und mit allen WWU-',
            'relevanten Kompetenzen ausgestattet sein.</p>'
        ];
        $out    = LineSplitter::splitHtmlToLines($orig, 92, '');
        $this->assertSame($expect, $out);
    }

    public function testWithLinenumbers(): void
    {
        $orig   = '<p>nationalen Parlamente sowie die Rückkehr zur Gemeinschaftsmethode und eine EU-Kommissarin oder einen EU-Kommissar; er oder sie soll der Eurogruppe vorsitzen und mit allen WWU-relevanten Kompetenzen ausgestattet sein.</p>';
        $expect = [
            '<p>###LINENUMBER###nationalen Parlamente sowie die Rückkehr zur Gemeinschaftsmethode und eine EU-Kommissarin ',
            '###LINENUMBER###oder einen EU-Kommissar; er oder sie soll der Eurogruppe vorsitzen und mit allen WWU-',
            '###LINENUMBER###relevanten Kompetenzen ausgestattet sein.</p>'
        ];
        $out    = LineSplitter::splitHtmlToLines($orig, 92, '###LINENUMBER###');
        $this->assertSame($expect, $out);
    }

    public function testMultilevelList(): void
    {
        $orig   = '<p>1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234</p>' .
            '<ul>
        <li>1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234</li>
        <li>
            <ul>
            <li>1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234</li>
            </ul>
        </li>
        <li>1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234</li>
        </ul>';
        $expect = [
            '<p>###LINENUMBER###1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 ',
            '###LINENUMBER###7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234</p>',
            '<ul><li>###LINENUMBER###1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 ',
            '###LINENUMBER###6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234</li>',
            '<li><ul><li>###LINENUMBER###1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 ',
            '###LINENUMBER###4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 ',
            '###LINENUMBER###7234 8234 9234 0234</li></ul></li>',
            '<li>###LINENUMBER###1234 2234 3234 4234 5234 6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 ',
            '###LINENUMBER###6234 7234 8234 9234 0234 1234 2234 3234 4234 5234 6234 7234 8234 9234 0234</li></ul>'
        ];
        $out    = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $this->assertSame($expect, $out);
    }

    public function testUl(): void
    {
        $orig   = '<ul><li>No. 1</li></ul>';
        $expect = [
            '<ul><li>###LINENUMBER###No. 1</li></ul>',
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $this->assertSame($expect, $out);
    }

    public function testBlockquote(): void
    {
        $orig   = '<blockquote><p>No. 1</p></blockquote>';
        $expect = [
            '<blockquote><p>###LINENUMBER###No. 1</p></blockquote>',
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $this->assertSame($expect, $out);
    }

    public function testOl(): void
    {
        $orig   = '<ol start="2"><li>No. 1</li></ol>';
        $expect = [
            '<ol start="2"><li>###LINENUMBER###No. 1</li></ol>',
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $this->assertSame($expect, $out);
    }

    public function testForceLinebreak(): void
    {
        $orig   = '<p><br><strong>Demokratie und Freiheit </strong><br>' . "\r\n" .
            'Demokratie und Freiheit gehören untrennbar zusammen.</p>';
        $expect = [
            '<p>###LINENUMBER###<br>',
            '###LINENUMBER###<strong>Demokratie und Freiheit </strong><br>' . "\n",
            '###LINENUMBER###Demokratie und Freiheit gehören untrennbar zusammen.</p>',
        ];
        $out    = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $this->assertSame($expect, $out);
    }

    public function testAmpersand(): void
    {
        $orig   = '<p>Test Line 1 &amp; ü € 2<br>Line 2</p>';
        $expect = [
            '<p>###LINENUMBER###Test Line 1 &amp; ü € 2<br>',
            '###LINENUMBER###Line 2</p>',
        ];
        $out    = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');

        $this->assertSame($expect, $out);
    }

    public function testHeadlines(): void
    {
        $orig   = '<h2>Wir kämpfen für Lohngleichheit und eine eigenständige Existenzsicherung von Frauen</h2>';
        $expect = [
            '<h2>###LINENUMBER###Wir kämpfen für Lohngleichheit und eine ',
            '###LINENUMBER###eigenständige Existenzsicherung von Frauen</h2>',
        ];

        $out = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $this->assertSame($expect, $out);
    }

    public function testExtractLinesBeginning(): void
    {
        $orig = '<ul><li>Bavaria ipsum <strong>dolor sit amet o’ha wea nia ausgähd,</strong> kummt nia hoam i hob di narrisch gean helfgod ebba ded baddscher.</li><li>Des so so, nia Biawambn back mas? Kaiwe Hetschapfah Trachtnhuat, a bravs. I moan scho aa Oachkatzlschwoaf Haberertanz Semmlkneedl, no Graudwiggal. </li></ul>';
        $extracted = LineSplitter::extractLines($orig, 30, 3, 3, 5);

        $this->assertSame('<ul><li>Bavaria ipsum <strong>dolor sit amet o’ha wea nia ausgähd,</strong> kummt nia hoam </li></ul>', $extracted);
    }

    public function testExtractLinesMiddle(): void
    {
        $orig = '<ul><li>Bavaria ipsum <strong>dolor sit amet o’ha wea nia ausgähd,</strong> kummt nia hoam i hob di narrisch gean helfgod ebba ded baddscher.</li><li>Des so so, nia Biawambn back mas? Kaiwe Hetschapfah Trachtnhuat, a bravs. I moan scho aa Oachkatzlschwoaf Haberertanz Semmlkneedl, no Graudwiggal. </li></ul>';
        $extracted = LineSplitter::extractLines($orig, 30, 1, 3, 5);

        $this->assertSame('ausgähd, kummt nia hoam i hob di narrisch gean helfgod ebba ded', $extracted);
    }
}
