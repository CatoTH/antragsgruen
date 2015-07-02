<?php

namespace unit;

use app\components\LaTeXExporter;
use app\components\LineSplitter;
use app\models\sectionTypes\TextSimple;
use Codeception\Specify;

class HTML2TexTest extends TestBase
{
    use Specify;


    public function testBold()
    {
        $orig     = '<p>Normaler Text <strong>fett</strong></p>';
        $expect = 'Normaler Text \textbf{fett}' . "\n";
        $out    = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testItalic()
    {
        $orig     = '<p>Normaler Text <em>kursiv</em></p>';
        $expect = 'Normaler Text \emph{kursiv}' . "\n";
        $out    = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testUnderlines()
    {
        $orig     = '<p>Normaler Text <span class="underline">unterstrichen</span></p>';
        $expect = 'Normaler Text \underline{unterstrichen}' . "\n";
        $out    = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);

        $orig     = '<p>Normaler Text <u>unterstrichen</u></p>';
        $expect = 'Normaler Text \underline{unterstrichen}' . "\n";
        $out    = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStrike()
    {
        $orig     = '<p>Normaler Text <span class="strike">durchgestrichen</span></p>';
        $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
        $out    = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);

        $orig     = '<p>Normaler Text <s>durchgestrichen</s></p>';
        $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
        $out    = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testBlockquote()
    {
        $orig     = '<p>Normaler Text</p><blockquote>Zitat</blockquote><p>Weiter</p>';
        $expect = 'Normaler Text' . "\n";
        $expect .= '\begin{quotation}Zitat\end{quotation}' . "\n";
        $expect .= 'Weiter' . "\n";
        $out = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testUnnumbered()
    {
        $orig     = '<ul><li>Punkt 1</li><li>Punkt 2</li></ul>';
        $expect = '\begin{itemize}' . "\n";
        $expect .= '\item Punkt 1' . "\n";
        $expect .= '\item Punkt 2' . "\n";
        $expect .= '\end{itemize}' . "\n";

        $out = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }


    public function testLinks()
    {
        $orig     = 'Test <a href="https://www.antragsgruen.de/">Antragsgrün</a> Ende';
        $expect = 'Test \href{https://www.antragsgruen.de/}{Antragsgrün} Ende';

        $out = LaTeXExporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testLineBreaks()
    {
        $orig     = '<p>Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla, halbseidener, ' .
            'Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.<br>' .
            'Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada, Oaschgsicht, ' .
            'greißlicha Uhu, oida Daddara!</p>';
        $expect = "Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla,\\linebreak\n" .
            "halbseidener, Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.\\newline\n" .
            "Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada,\\linebreak\n" .
            "Oaschgsicht, greißlicha Uhu, oida Daddara!\n";

        $lines = LineSplitter::motionPara2lines($orig, true, 80);
        $out   = TextSimple::getMotionLinesToTeX($lines);
        $this->assertEquals($expect, $out);
    }
}
