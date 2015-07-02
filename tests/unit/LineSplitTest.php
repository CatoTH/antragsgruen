<?php

namespace unit;

use app\components\LineSplitter;
use Codeception\Specify;

class LineSplitTest extends TestBase
{
    use Specify;

    public function testCase1()
    {
        $orig     = "Geschäftsordnung der Bundesversammlung geregelt. " .
            "Antragsberechtigt sind die Orts- und Kreisverbände, die " .
            "Landesversammlungen bzw. Landesdelegiertenkonferenzen,";
        $expect = [
            "Geschäftsordnung der Bundesversammlung geregelt. Antragsberechtigt sind die",
            "Orts- und Kreisverbände, die Landesversammlungen bzw.",
            "Landesdelegiertenkonferenzen,"
        ];

        $splitter = new LineSplitter($orig, 80);
        $out      = $splitter->splitLines();

        $this->assertEquals($expect, $out);
    }

    public function testCase2()
    {
        $orig     = "gut und richtig, wenn Eltern selbst eine Initiative für " .
            "Kinderbetreuung gründen – besser ist";
        $expect = [
            "gut und richtig, wenn Eltern selbst eine Initiative für Kinderbetreuung gründen",
            "– besser ist"
        ];

        $splitter = new LineSplitter($orig, 80);
        $out      = $splitter->splitLines();

        $this->assertEquals($expect, $out);
    }

    public function testCase3()
    {
        $orig     = "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, " .
            "wenn sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. " .
            "Es ist gut und richtig,";
        $expect = [
            "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, wenn",
            "sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. Es ist",
            "gut und richtig,"
        ];

        $splitter = new LineSplitter($orig, 80);
        $out      = $splitter->splitLines();

        $this->assertEquals($expect, $out);
    }

    public function testCase4()
    {
        $orig     = "angehen, ist von gro&szlig;em Wert f&uuml;r unser Land. Ver&auml;nderung " .
            "kann nur gelingen, wenn sie von Vielen ";
        $expect = [
            "angehen, ist von gro&szlig;em Wert f&uuml;r unser Land. Ver&auml;nderung kann nur gelingen, wenn",
            "sie von Vielen "
        ];

        $splitter = new LineSplitter($orig, 80);
        $out      = $splitter->splitLines();

        $this->assertEquals($expect, $out);
    }

    public function testCase5()
    {
        $orig     = "1angehen, ist von<br>gro&szlig;em Wert f&uuml;r<br>\nunser Land. Ver&auml;nderung " .
            "kann nur gelingen, wenn sie von Vielen sdfsdf sdfsdsdf dfdfs sf d";
        $expect = [
            "1angehen, ist von###FORCELINEBREAK###",
            "gro&szlig;em Wert f&uuml;r###FORCELINEBREAK###",
            "unser Land. Ver&auml;nderung kann nur gelingen, wenn sie von Vielen sdfsdf sdfsdsdf",
            "dfdfs sf d"
        ];

        $splitter = new LineSplitter($orig, 80);
        $out      = $splitter->splitLines();

        $this->assertEquals($expect, $out);
    }
}
