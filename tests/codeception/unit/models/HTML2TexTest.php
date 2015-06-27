<?php

namespace app\tests\codeception\unit\models;

use app\components\LaTeXExporter;
use app\components\LineSplitter;
use app\models\sectionTypes\TextSimple;
use yii\codeception\TestCase;
use Codeception\Specify;

class HTML2TexTest extends TestCase
{
    use Specify;

    /**
     *
     */
    public function testFormats()
    {
        $this->specify(
            'Testing Bold',
            function () {
                $in     = '<p>Normaler Text <strong>fett</strong></p>';
                $expect = 'Normaler Text \textbf{fett}' . "\n";
                $out    = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Italic',
            function () {
                $in     = '<p>Normaler Text <em>kursiv</em></p>';
                $expect = 'Normaler Text \emph{kursiv}' . "\n";
                $out    = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Underlined',
            function () {
                $in     = '<p>Normaler Text <span class="underline">unterstrichen</span></p>';
                $expect = 'Normaler Text \underline{unterstrichen}' . "\n";
                $out    = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);

                $in     = '<p>Normaler Text <u>unterstrichen</u></p>';
                $expect = 'Normaler Text \underline{unterstrichen}' . "\n";
                $out    = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Strike-Through',
            function () {
                $in     = '<p>Normaler Text <span class="strike">durchgestrichen</span></p>';
                $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
                $out    = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);

                $in     = '<p>Normaler Text <s>durchgestrichen</s></p>';
                $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
                $out    = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Blockquote',
            function () {
                $in     = '<p>Normaler Text</p><blockquote>Zitat</blockquote><p>Weiter</p>';
                $expect = 'Normaler Text' . "\n";
                $expect .= '\begin{quotation}Zitat\end{quotation}' . "\n";
                $expect .= 'Weiter' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Unnumbered List',
            function () {
                $in     = '<ul><li>Punkt 1</li><li>Punkt 2</li></ul>';
                $expect = '\begin{itemize}' . "\n";
                $expect .= '\item Punkt 1' . "\n";
                $expect .= '\item Punkt 2' . "\n";
                $expect .= '\end{itemize}' . "\n";

                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Testing Links',
            function () {
                $in     = 'Test <a href="https://www.antragsgruen.de/">Antragsgrün</a> Ende';
                $expect = 'Test \href{https://www.antragsgruen.de/}{Antragsgrün} Ende';

                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );


        $this->specify(
            'Testing line breaks',
            function () {
                $in     = '<p>Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla, halbseidener, ' .
                    'Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.<br>' .
                    'Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada, Oaschgsicht, ' .
                    'greißlicha Uhu, oida Daddara!</p>';
                $expect = "Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla,\\linebreak\n" .
                    "halbseidener, Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.\\newline\n" .
                    "Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada,\\linebreak\n" .
                    "Oaschgsicht, greißlicha Uhu, oida Daddara!\n";

                $lines = LineSplitter::motionPara2lines($in, true, 80);
                $out = TextSimple::getMotionLinesToTeX($lines);
                $this->assertEquals($expect, $out);
            }
        );
    }
}
