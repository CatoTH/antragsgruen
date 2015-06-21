<?php

namespace app\tests\codeception\unit\models;

use app\components\LaTeXExporter;
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
                $in = '<p>Normaler Text <strong>fett</strong></p>';
                $expect = 'Normaler Text \textbf{fett}' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Italic',
            function () {
                $in = '<p>Normaler Text <em>kursiv</em></p>';
                $expect = 'Normaler Text \emph{kursiv}' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Underlined',
            function () {
                $in = '<p>Normaler Text <span class="underline">unterstrichen</span></p>';
                $expect = 'Normaler Text \underline{unterstrichen}' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);

                $in = '<p>Normaler Text <u>unterstrichen</u></p>';
                $expect = 'Normaler Text \underline{unterstrichen}' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Strike-Through',
            function () {
                $in = '<p>Normaler Text <span class="strike">durchgestrichen</span></p>';
                $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);

                $in = '<p>Normaler Text <s>durchgestrichen</s></p>';
                $expect = 'Normaler Text \sout{durchgestrichen}' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Blockquote',
            function () {
                $in = '<p>Normaler Text</p><blockquote>Zitat</blockquote><p>Weiter</p>';
                $expect = 'Normaler Text' . "\n";
                $expect .= '\begin{quotation}Zitat\end{quotation}'. "\n";
                $expect .= 'Weiter' . "\n";
                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Testing Unnumbered List',
            function () {
                $in = '<ul><li>Punkt 1</li><li>Punkt 2</li></ul>';
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
                $in = 'Test <a href="https://www.antragsgruen.de/">Antragsgrün</a> Ende';
                $expect = 'Test \href{https://www.antragsgruen.de/}{Antragsgrün} Ende';

                $out = LaTeXExporter::encodeHTMLString($in);
                $this->assertEquals($expect, $out);
            }
        );




        $this->specify(
            'Testing All',
            function () {
                /*
                 <p>Normaler Text <strong>fett und <em>kursiv</em></strong><br>
    Zeilenumbruch <span class="underline">unterstrichen</span></p>
<p><span class="strike">Durchgestrichen und <em>kursiv</em></span></p>
<ol><li>Listenpunkt</li>
    <li>Listenpunkt (<em>kursiv</em>)<br>
        Zeilenumbruch</li>
</ol><ul>
    <li>Einfache Punkte</li>
    <li>Nummer 2</li>
</ul>
<p>Link Bla</p>
<blockquote>
    <p>Zitat 223<br>
        Zeilenumbruch</p>
    <p>Neuer Paragraph</p>
</blockquote>
<p>Ende</p>

\par Normaler TextÂ~\textbf{fett undÂ~\emph{kursiv}}\\
    ZeilenumbruchÂ~unterstrichen
\par Durchgestrichen undÂ~\emph{kursiv}
\begin{enumerate}\item Listenpunkt
    \item Listenpunkt (\emph{kursiv})\\
        Zeilenumbruch
\end{enumerate}\begin{itemize}
    \item Einfache Punkte
    \item Nummer 2
\end{itemize}
\par LinkÂ~Bla
\begin{quotation}
    \par Zitat 223\\
        Zeilenumbruch
    \par Neuer Paragraph
\end{quotation}
\par Ende

                 */
            }
        );
    }
}
