<?php

namespace app\tests\codeception\unit\models;

use app\components\LineSplitter;
use Codeception\Specify;
use yii\codeception\TestCase;

class LineSplitTest extends TestCase
{
    use Specify;

    /**
     *
     */
    public function testParagraphs()
    {
        $this->specify(
            'Line Splitting 1',
            function () {
                $in     = "Geschäftsordnung der Bundesversammlung geregelt. " .
                    "Antragsberechtigt sind die Orts- und Kreisverbände, die " .
                    "Landesversammlungen bzw. Landesdelegiertenkonferenzen,";
                $expect = [
                    "Geschäftsordnung der Bundesversammlung geregelt. Antragsberechtigt sind die",
                    "Orts- und Kreisverbände, die Landesversammlungen bzw.",
                    "Landesdelegiertenkonferenzen,"
                ];

                $splitter = new LineSplitter($in, 80);
                $out      = $splitter->splitLines(false);

                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Line Splitting 2',
            function () {
                $in     = "gut und richtig, wenn Eltern selbst eine Initiative für " .
                    "Kinderbetreuung gründen – besser ist";
                $expect = [
                    "gut und richtig, wenn Eltern selbst eine Initiative für Kinderbetreuung gründen",
                    "– besser ist"
                ];

                $splitter = new LineSplitter($in, 80);
                $out      = $splitter->splitLines(false);

                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Line Splitting 3',
            function () {
                $in     = "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, " .
                    "wenn sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. " .
                    "Es ist gut und richtig,";
                $expect = [
                    "angehen, ist von großem Wert für unser Land. Veränderung kann nur gelingen, wenn",
                    "sie von Vielen getragen wird. Aber Veränderung braucht auch die Politik. Es ist",
                    "gut und richtig,"
                ];

                $splitter = new LineSplitter($in, 80);
                $out      = $splitter->splitLines(false);

                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Line Splitting 4',
            function () {
                $in     = "angehen, ist von gro&szlig;em Wert f&uuml;r unser Land. Ver&auml;nderung " .
                    "kann nur gelingen, wenn sie von Vielen ";
                $expect = [
                    "angehen, ist von gro&szlig;em Wert f&uuml;r unser Land. Ver&auml;nderung kann nur gelingen, wenn",
                    "sie von Vielen "
                ];

                $splitter = new LineSplitter($in, 80);
                $out      = $splitter->splitLines(false);

                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Line Splitting 5',
            function () {
                $in     = "1angehen, ist von<br>gro&szlig;em Wert f&uuml;r<br>\nunser Land. Ver&auml;nderung " .
                    "kann nur gelingen, wenn sie von Vielen sdfsdf sdfsdsdf dfdfs sf d";
                $expect = [
                    "1angehen, ist von",
                    "gro&szlig;em Wert f&uuml;r",
                    "unser Land. Ver&auml;nderung kann nur gelingen, wenn sie von Vielen sdfsdf sdfsdsdf",
                    "dfdfs sf d"
                ];

                $splitter = new LineSplitter($in, 80);
                $out      = $splitter->splitLines(false);

                $this->assertEquals($expect, $out);
            }
        );
    }
}
