<?php

namespace unit;

use app\components\latex\Exporter;
use app\components\LineSplitter;
use app\models\sectionTypes\TextSimple;
use Codeception\Specify;

class HTML2TexTest extends TestBase
{
    use Specify;

    public function testEmptyLine()
    {
        $orig   = "<p> </p>";
        $expect = "{\\color{white}.}\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);

        $orig   = "<p>###LINENUMBER### </p>";
        $expect = "###LINENUMBER###{\\color{white}.}\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testLineBreaks()
    {
        $orig   = [
            '<p>###LINENUMBER###Normaler Text <strong>fett und <em>kursiv</em></strong>###FORCELINEBREAK###',
            '###LINENUMBER###Zeilenumbruch <span class="underline">unterstrichen</span></p>',
        ];
        $expect = 'Normaler Text \textbf{fett und \emph{kursiv}}' . "\n" .
            '\\\\' . "\n" .
            'Zeilenumbruch \uline{unterstrichen}' . "\n";
        $out    = TextSimple::getMotionLinesToTeX($orig);
        $this->assertEquals($expect, $out);

        $orig   = '<p>Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla, halbseidener, ' .
            'Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.<br>' .
            'Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada, Oaschgsicht, ' .
            'greißlicha Uhu, oida Daddara!</p>';
        $expect = "Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla,\\linebreak\n" .
            "halbseidener, Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.\n" .
            "\\\\\n" .
            "Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada,\\linebreak\n" .
            "Oaschgsicht, greißlicha Uhu, oida Daddara!\n";

        $lines = LineSplitter::motionPara2lines($orig, true, 80);
        $out   = TextSimple::getMotionLinesToTeX($lines);
        $this->assertEquals($expect, $out);
    }

    public function testBold()
    {
        $orig   = '<p>Normaler Text <strong>fett</strong></p>';
        $expect = 'Normaler Text \textbf{fett}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testItalic()
    {
        $orig   = '<p>Normaler Text <em>kursiv</em></p>';
        $expect = 'Normaler Text \emph{kursiv}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testUnderlines()
    {
        $orig   = '<p>Normaler Text <span class="underline">unterstrichen</span></p>';
        $expect = 'Normaler Text \uline{unterstrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);

        $orig   = '<p>Normaler Text <u>unterstrichen</u></p>';
        $expect = 'Normaler Text \uline{unterstrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStrike()
    {
        $orig   = '<p>Normaler Text <span class="strike">durchgestrichen</span></p>';
        $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);

        $orig   = '<p>Normaler Text <s>durchgestrichen</s></p>';
        $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testBlockquote()
    {
        $orig   = '<p>Normaler Text</p><blockquote>Zitat</blockquote><p>Weiter</p>';
        $expect = 'Normaler Text' . "\n";
        $expect .= '\begin{quotation}\noindent' . "\n" . 'Zitat\end{quotation}' . "\n";
        $expect .= 'Weiter' . "\n";
        $out = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }

    public function testUnnumbered()
    {
        $orig   = '<ul><li>Punkt 1</li><li>Punkt 2</li></ul>';
        $expect = '\begin{itemize}' . "\n";
        $expect .= '\item Punkt 1' . "\n";
        $expect .= '\item Punkt 2' . "\n";
        $expect .= '\end{itemize}' . "\n";

        $out = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }


    public function testLinks()
    {
        $orig   = 'Test <a href="https://www.antragsgruen.de/">Antragsgrün</a> Ende';
        $expect = 'Test \href{https://www.antragsgruen.de/}{Antragsgrün} Ende';

        $out = Exporter::encodeHTMLString($orig);
        $this->assertEquals($expect, $out);
    }
}
