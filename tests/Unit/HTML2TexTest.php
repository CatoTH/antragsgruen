<?php

namespace Tests\Unit;

use app\components\latex\Exporter;
use app\components\LineSplitter;
use Tests\Support\Helper\TestBase;

class HTML2TexTest extends TestBase
{

    public function testEmptyLine(): void
    {
        $orig   = "<p> </p>";
        $expect = "{\\color{white}.}\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);

        $orig   = "<p>###LINENUMBER### </p>";
        $expect = "###LINENUMBER###{\\color{white}.}\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testLineBreaks(): void
    {
        $orig   = [
            '<p>###LINENUMBER###Normaler Text <strong>fett und <em>kursiv</em></strong><br>',
            '###LINENUMBER###Zeilenumbruch <span class="underline">unterstrichen</span></p>',
        ];
        $expect = 'Normaler Text \textbf{fett und \emph{kursiv}}\linebreak{}' . "\n" .
            'Zeilenumbruch \uline{unterstrichen}' . "\n";
        $out    = Exporter::getMotionLinesToTeX($orig);
        $this->assertSame($expect, $out);

        $orig   = '<p>Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla, halbseidener, ' .
            'Aufmüpfiga, Voiksdepp, Gibskobf, Kasberlkopf.<br>' .
            'Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada, Oaschgsicht, ' .
            'greißlicha Uhu, oida Daddara!</p>';
        $expect = "Doafdebb, Asphaltwanzn, hoid dei Babbn, Schdeckalfisch, Hemmadbiesla,\\linebreak{}\n" .
            "halbseidener, Aufm\\\"upfiga, Voiksdepp, Gibskobf, Kasberlkopf.\\linebreak{}\n" .
            "Flegel, Kamejtreiba, glei foid da Wadschnbam um, schdaubiga Bruada, Oaschgsicht,\\linebreak{}\n" .
            "grei\\ss{}licha Uhu, oida Daddara!\n";

        $lines = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $out   = Exporter::getMotionLinesToTeX($lines);
        $this->assertSame($expect, $out);


        $orig   = "<p><br></p>";
        $expect = "{\\color{white}.}\n";
        $out    = Exporter::getMotionLinesToTeX([$orig]);
        $this->assertSame($expect, $out);
    }

    public function testBold(): void
    {
        $orig   = '<p>Normaler Text <strong>fett</strong></p>';
        $expect = 'Normaler Text \textbf{fett}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testItalic(): void
    {
        $orig   = '<p>Normaler Text <em>kursiv</em></p>';
        $expect = 'Normaler Text \emph{kursiv}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testUnderlines(): void
    {
        $orig   = '<p>Normaler Text <span class="underline">unterstrichen</span></p>';
        $expect = 'Normaler Text \uline{unterstrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);

        $orig   = '<p>Normaler Text <u>unterstrichen</u></p>';
        $expect = 'Normaler Text \uline{unterstrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testStrike(): void
    {
        $orig   = '<p>Normaler Text <span class="strike">durchgestrichen</span></p>';
        $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);

        $orig   = '<p>Normaler Text <s>durchgestrichen</s></p>';
        $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testBlockquote(): void
    {
        $orig   = '<p>Normaler Text</p><blockquote>Zitat</blockquote><p>Weiter</p>';
        $expect = 'Normaler Text' . "\n";
        $expect .= '\begin{quotation}\noindent' . "\n" . 'Zitat\end{quotation}' . "\n";
        $expect .= 'Weiter' . "\n";
        $out = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testUnnumbered(): void
    {
        $orig   = '<ul><li>Punkt 1</li><li>Punkt 2</li></ul>';
        $expect = '\begin{itemize}' . "\n";
        $expect .= '\item Punkt 1' . "\n";
        $expect .= '\item Punkt 2' . "\n";
        $expect .= '\end{itemize}' . "\n";

        $out = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testLinks(): void
    {
        $orig   = 'Test <a href="https://www.antragsgruen.de/?q=Antragsgr%C3%BCn">Antragsgrün</a> Ende';
        $expect = 'Test \href{https://www.antragsgruen.de/?q=Antragsgr\%C3\%BCn}{Antragsgr\"un} Ende';

        $out = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testBrokenHtml(): void
    {
        $orig   = "<p>Test <em>kursiv</em> <ins>Neu</ins> </strong></p>";
        $expect = "Test \\emph{kursiv} \\textcolor{Insert}{\\uline{Neu}}\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testInserted(): void
    {
        $orig   = "<p class='inserted'>Neu <em>Neu2</em></p>";
        $expect = '\textcolor{Insert}{\uline{Neu}\uline{ }}\emph{\textcolor{Insert}{\uline{Neu2}}}' . "\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testDeleted(): void
    {
        $orig   = "<p class='deleted'>Neu Neu2</p>";
        $expect = "\\textcolor{Delete}{\sout{Neu}\sout{ Neu2}}\n";
        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testNestedLists(): void
    {
        $orig = [
            '<ol start="4"><li>###LINENUMBER###Test 2' . "\n",
            '<ol class="decimalCircle"><li>###LINENUMBER###Test a</li>',
            '<li value="g">###LINENUMBER###Test c</li>',
            '<li value="i/">###LINENUMBER###Test d</li>',
            '<li>###LINENUMBER###Test d</li></ol></li>',
            '<li>Test 5</li></ol>'
        ];
        $expect = '\begin{enumerate}
\item[4.] Test 2
\begin{enumerate}
\item[(1)] Test a
\item[(g)] Test c
\item[(i/)] Test d
\item[(9)] Test d
\end{enumerate}
\item[5.] Test 5
\end{enumerate}' . "\n";
        $out    = Exporter::getMotionLinesToTeX($orig);
        $this->assertSame($expect, $out);
    }

    public function testDeletedInsertedLists(): void
    {
        $orig = '<div><ol class="deleted" start="4"><li value="4">Test 2' . "\n" . '<ol class="decimalCircle"><li>Test a</li><li value="g">Test c</li><li value="i/">Test d</li><li>Test 9</li></ol></li></ol>' .
                '<ol class="inserted" start="2"><li>Test3</li></ol><ol class="deleted" start="5"><li>Test3</li></ol></div>';

        $expect = '\begin{enumerate}
\item[4.] \textcolor{Delete}{\sout{Test}\sout{ 2}\sout{ }}\begin{enumerate}
\item[(1)] \textcolor{Delete}{\sout{Test}\sout{ a}}
\item[(g)] \textcolor{Delete}{\sout{Test}\sout{ c}}
\item[(i/)] \textcolor{Delete}{\sout{Test}\sout{ d}}
\item[(9)] \textcolor{Delete}{\sout{Test}\sout{ 9}}
\end{enumerate}

\end{enumerate}
\begin{enumerate}
\item[2.] \textcolor{Insert}{\uline{Test3}}
\end{enumerate}
\begin{enumerate}
\item[5.] \textcolor{Delete}{\sout{Test3}}
\end{enumerate}

';

        $out = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testParagraphsAndLineBreaksInLists(): void
    {
        $orig = '<ul>
 <li>
 <p>Line 1</p>
 <ul>
 <li>
 <p>Line 2</p>
<br>
</li>
</ul>
</li>
</ul>';
        $expect = '\begin{itemize}
\item   Line 1
\begin{itemize}
\item   Line 2
\newline

\end{itemize}

\end{itemize}
';

        $out    = Exporter::encodeHTMLString($orig);

        $this->assertSame($expect, $out);
    }

    public function testNestedListsWithEmptyLines(): void
    {
        $orig = '<ul>
 <li>
 <p>Line 1</p>
<br>
</li>
 <li>
 <p>Line 2</p>
 <ul>
 <li>
 <p>Line 2.1.</p>
<br>
</li>
 <li>
 <p>Line 2.2</p>
</li>
 </ul>
<br>
</li>
<li>
<p>Line 3</p>
</li>
</ul>';
        $expect = '\begin{itemize}
\item   Line 1
\newline

\item   Line 2
\begin{itemize}
\item   Line 2.1.
\newline

\item   Line 2.2

\end{itemize}
\phantom{ }

\item  Line 3

\end{itemize}
';

        $out    = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testNestedListsWithEmptyLines2(): void
    {
        $orig = '<ul>
 <li>
 <p>Line 1</p>
<br>
</li>
 <li>
 <p>Line 2</p>
 <ul>
 <li>
 <p>Line 2.1.</p>
<br>
</li>
 <li>
 <p>Line 2.2</p>
</li>
 </ul>
<br>
</li>
<li>
<p>Line 3</p>
</li>
</ul>';
        $expect = '\begin{itemize}
\item Line 1
 \newline
\item Line 2
\begin{itemize}
\item Line 2.1.
 \newline
\item Line 2.2

\end{itemize}
 \phantom{ }
\item Line 3

\end{itemize}
';

        $byLines = LineSplitter::splitHtmlToLines($orig, 80, '###LINENUMBER###');
        $out = Exporter::getMotionLinesToTeX($byLines);
        $out = Exporter::fixLatexErrorsInFinalDocument($out);

        $this->assertSame($expect, $out);
    }

    public function testListInBlock(): void
    {
        $orig = '<blockquote class="delete"><ul><li>Test 123</li></ul>';
        $expect = '\begin{quotation}
\begin{itemize}
\item Test 123
\end{itemize}
\end{quotation}
';
        $out = Exporter::encodeHTMLString($orig);
        $this->assertSame($expect, $out);
    }

    public function testDoubleBr(): void
    {
        $orig = '<p>First line<br>
<br>
Second line.</p>';
        $expect = 'First line\newline
\phantom{ }

Second line.
';

        $out = Exporter::getMotionLinesToTeX([$orig]);
        $out = Exporter::fixLatexErrorsInFinalDocument($out);
        $this->assertSame($expect, $out);
    }

    public function testBrAfterBlockquote(): void
    {
        $orig = '<p>Test1</p><blockquote>
<p><strong>Line 1,<br />
Line 2,<br />
Line 3, </strong><br />
<strong>Line 4 ...</strong><br />
&nbsp;</p>
</blockquote>
<p><br />
Test2</p>';
        $expected = 'Test1
\begin{quotation}\noindent
\textbf{Line 1,\newline
Line 2,\newline
Line 3, }\newline
\textbf{Line 4 ...}\newline
\end{quotation}
\phantom{ }
Test2
';

        $out = Exporter::getMotionLinesToTeX([$orig]);
        $out = Exporter::fixLatexErrorsInFinalDocument($out);

        $this->assertSame($expected, $out);
    }
}
