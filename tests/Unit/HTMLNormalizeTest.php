<?php

namespace Tests\Unit;

use app\components\HTMLTools;
use Tests\Support\Helper\TestBase;

class HTMLNormalizeTest extends TestBase
{
    public function testCorrectLeavesAria(): void
    {
        $htmlIn = 'Test <ins aria-label="Inserted: 1">1</ins><del aria-label="Deleted: 2">2</del><span>';
        $htmlOut = HTMLTools::correctHtmlErrors($htmlIn);
        $expect = 'Test <ins aria-label="Inserted: 1">1</ins><del aria-label="Deleted: 2">2</del><span></span>';

        $this->assertEquals($expect, $htmlOut);
    }

    public function testStrippingEmptyBlocks(): void
    {
        $htmlIn = '<p><del class="ice-del ice-cts" data-changedata="" data-cid="2" data-last-change-time="1497797183731" data-time="1497797183731" data-userid="" data-username="">Weit hinten, hinter den Wortbergen, fern der L&auml;nder Vokalien und Konsonantien leben die Blindtexte. Abgeschieden wohnen sie in Buchstabhausen an der K&uuml;ste des Semantik, eines gro&szlig;en Sprachozeans. Ein kleines B&auml;chlein namens Duden flie&szlig;t durch ihren Ort und versorgt sie mit den n&ouml;tigen Regelialien.</del></p>

<ul>
	<li>
	<p>Es ist ein paradiesmatisches Land, in dem einem gebratene Satzteile in den Mund fliegen</p>
	</li>
	<li>
	<p><del class="ice-del ice-cts" data-changedata="" data-cid="2" data-last-change-time="1497797183731" data-time="1497797183731" data-userid="" data-username="">Nicht einmal von der allm&auml;chtigen Interpunktion werden die Blindtexte beherrscht.</del></p>

	<ul>
		<li class="ice-del ice-cts">
		<p>ein geradezu unorthographisches Leben.</p>
		</li>
		<li>
		<p><del class="ice-del ice-cts" data-changedata="" data-cid="2" data-last-change-time="1497797183731" data-time="1497797183731" data-userid="" data-username="">Der gro&szlig;e Oxmox riet ihr davon ab, da es dort wimmele von b&ouml;sen Kommata, wilden Fragezeichen und hinterh&auml;ltigen Semikoli, doch das Blindtextchen lie&szlig; sich nicht beirren.</del></p>
		</li>
	</ul>
	</li>
	<li>
	<p><del class="ice-del ice-cts" data-changedata="" data-cid="2" data-last-change-time="1497797183731" data-time="1497797183731" data-userid="" data-username="">Es packte seine sieben Versalien, schob sich sein Initial in den G&uuml;rtel und machte sich auf den Weg.</del></p>
	</li>
</ul>
<p>Test remains</p>
<p><del class="ice-del ice-cts" data-changedata="" data-cid="2" data-last-change-time="1497797183731" data-time="1497797183731" data-userid="" data-username="">Wehm&uuml;tig lief ihm eine rhetorische Frage &uuml;ber die Wange, dann setzte es seinen Weg fort. Unterwegs traf es eine Copy.</del></p>
';
        $htmlOut = HTMLTools::stripEmptyBlockParagraphs(HTMLTools::cleanSimpleHtml($htmlIn));
        $expect = "<ul>\n<li>\n<p>Es ist ein paradiesmatisches Land, in dem einem gebratene Satzteile in den Mund fliegen</p>\n</li>\n" .
            "<li>\n<ul>\n <li>\n <p>ein geradezu unorthographisches Leben.</p>\n</li>\n</ul>\n</li>\n" .
            "</ul>\n<p>Test remains</p>";

        $this->assertEquals($expect, $htmlOut);
    }

    public function testWrapPureTextWithP(): void
    {
        $textIn = "<p>Normal Text</p>\nText with no parent element<p>Normal text again</p>";
        $expect = "<p>Normal Text</p>\n<p>Text with no parent element</p>\n<p>Normal text again</p>";
        $out    = HTMLTools::cleanSimpleHtml($textIn);
        $this->assertEquals($expect, $out);
    }

    public function testWrapInlinedTextWithP(): void
    {
        $textIn = '<p>Normal Text</p>Text <strong>with no</strong> parent <em>element</em><p>Normal text again</p>';
        $expect = "<p>Normal Text</p>\n<p>Text <strong>with no</strong> parent <em>element</em></p>\n<p>Normal text again</p>";
        $out    = HTMLTools::cleanSimpleHtml($textIn);
        $this->assertEquals($expect, $out);
    }

    public function testStripEmptySpans(): void
    {
        $orig   = '<p>Test</p><p><span class="underlined"><span>&nbsp;</span></span></p>';
        $expect = '<p>Test</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $out = HTMLTools::cleanSimpleHtml($out);
        $this->assertEquals($expect, $out);
    }

    public function testStripEmptyAs(): void
    {
        $orig   = '<h2><a>Test</a> - <a href="https://antragsgruen.de">Test 2</a></h2>';
        $expect = '<h2>Test - <a href="https://antragsgruen.de">Test 2</a></h2>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $out = HTMLTools::cleanSimpleHtml($out);
        $this->assertEquals($expect, $out);
    }

    public function testDanglingNbsp(): void
    {
        $orig   = '<p><span>so ein Fahrradanteil von 30 % und mehr erreicht werden kann.&nbsp;</span></p>';
        $expect = '<p>so ein Fahrradanteil von 30 % und mehr erreicht werden kann.</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripLeadingSpaces1(): void
    {
        $orig   = '<p> Test 123 </p>';
        $expect = '<p>Test 123</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripLeadingSpaces2(): void
    {
        $orig   = '<p>Test1<br><br> Test2</p>';
        $expect = '<p>Test1<br>' . "\n" . '<br>' . "\n" . 'Test2</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testMergingOls1(): void
    {
        $orig   = '<ol><li>List item 1</li></ol>' . "\n\t" . '<ol start="2"><li>List item 2</li></ol>';
        $expect = '<ol><li>List item 1</li>' . "\n" . '<li>List item 2</li>' . "\n" . '</ol>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testMergingOls2(): void
    {
        $orig   = '<ol start="1" class="upperAlpha">
	<li>Listenpunkt</li>
</ol>

<ol start="2" class="upperAlpha">
	<li>Listenpunkt (<em>kursiv</em>)<br />
	Zeilenumbruch</li>
</ol>';
        $expect = '<ol class="upperAlpha" start="1">
<li>Listenpunkt</li>
<li>Listenpunkt (<em>kursiv</em>)<br>
Zeilenumbruch</li>
</ol>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testOlsWithValues(): void
    {
        $orig   = '<ol class="decimalCircle invalidClass"><li value="2">List item 1</li><li value="3b">List item 2</li></ol>';
        $expect = '<ol class="decimalCircle"><li value="2">List item 1</li>' . "\n" . '<li value="3b">List item 2</li>' . "\n" . '</ol>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testPrepareForCKEditor1(): void
    {
        $orig   = '<p><strong> Test</strong></p> Test2';
        $expect = '<p><strong>&nbsp;Test</strong></p> Test2';
        $out    = HTMLTools::prepareHTMLForCkeditor($orig);
        $this->assertEquals($expect, $out);
    }

    public function testPrepareForCKEditor2(): void
    {
        $orig   = '<ol><li>List item 1</li></ol>' . "\n" . '<ol start="2"><li>List item 2</li></ol>';
        $expect = '<ol><li>List item 1</li><li>List item 2</li></ol>';
        $out    = HTMLTools::prepareHTMLForCkeditor($orig);
        $this->assertEquals($expect, $out);
    }

    public function testCreateParagraphs(): void
    {
        $orig   = "<p>Test<br><span style='color: red;'>Test2</span><br><span></span>";
        $expect = "<p>Test<br>\nTest2<br>\n";

        $orig   .= "<strong onClick=\"alert('Alarm!');\">Test3</strong><br><br>\r\r";
        $expect .= "<strong>Test3</strong><br>\n<br>\n";

        $orig   .= "Test4</p><ul><li>Test</li>\r";
        $expect .= "Test4</p>\n<ul>\n<li>Test</li>\n";

        $orig   .= "<li>Test2\n<s>Test3</s></li>\r\n\r\n</ul>";
        $expect .= "<li>Test2\nTest3</li>\n</ul>\n";

        $orig   .= "<p><a href='http://www.example.org/'>Example</a><u>Underlined</u></p>";
        $expect .= "<p><a href=\"http://www.example.org/\">Example</a>Underlined</p>";

        $orig   .= "<!-- Comment -->";
        $expect .= "";

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testAllowUnderlines(): void
    {
        $orig   = "<p><span class='underline'>Underlined</span> Normal</p>";
        $expect = '<p><span class="underline">Underlined</span> Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testAllowH1H2H3H4(): void
    {
        $orig   = '<h1>H1</h1><h2>H2</h2><h3>H3</h3><h4>H4</h4>';
        $expect = '<h1>H1</h1><h2>H2</h2><h3>H3</h3><h4>H4</h4>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testAllowStrike(): void
    {
        $orig   = "<p><span class='strike'>Strike</span> Normal</p>";
        $expect = '<p><span class="strike">Strike</span> Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testAllowSubscript(): void
    {
        $orig   = "<p><span class='subscript'>Subscript</span> Normal</p>";
        $expect = '<p><span class="subscript">Subscript</span> Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $orig   = "<p><sub>Subscript</sub> Normal</p>";
        $expect = '<p><sub>Subscript</sub> Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testSuperscript(): void
    {
        $orig   = "<p><span class='superscript'>Superscript</span> Normal</p>";
        $expect = '<p><span class="superscript">Superscript</span> Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $orig   = "<p><sup>Superscript</sup> Normal</p>";
        $expect = '<p><sup>Superscript</sup> Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripUnknown(): void
    {
        $orig   = "<p><span class='unknown'>Strike</span> Normal</p>";
        $expect = '<p>Strike Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);


        $orig   = "<p><span class='strike unknown'>Strike</span> Normal</p>";
        $expect = '<p><span class="strike">Strike</span> Normal</p>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripBRsAtBeginningAndEnd(): void
    {
        $orig   = "<p>Test1</p>\n<p><br>\nTest2<br></p>";
        $expect = "<p>Test1</p>\n<p>Test2</p>";
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripBRsWithNbspsAtBeginningAndEnd1(): void
    {
        $orig = "<p>Test1</p>\n<p class='underline'><br>\nTest2<br>&nbsp;</p>";
        $expect = "<p>Test1</p>\n<p class=\"underline\">Test2</p>";
        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripBRsWithNbspsAtBeginningAndEnd2(): void
    {
        $orig = "<ol class='decimalCircle'><li><span><span><span><span>Test123</span></span></span></span><br /> &nbsp;</li></ol>";
        $expect = '<ol class="decimalCircle"><li>Test123</li>' . "\n" . '</ol>';
        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripBRsWithNbspsAtBeginningAndEnd3(): void
    {
        $orig = "<p>&nbsp;<br />\nTest</p>";
        $expect = "<p>Test</p>";
        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripWhitespacesAtEnd(): void
    {
        $orig   = '<p>Test 123 </p>';
        $expect = '<p>Test 123</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $orig   = '<p>Test 123 <br>Test 123</p>';
        $expect = '<p>Test 123<br>' . "\n" . 'Test 123</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $orig   = "<ul>\n<li>Test 123 </li>\n</ul>";
        $expect = "<ul>\n<li>Test 123</li>\n</ul>";
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripMultipleSpaces(): void
    {
        $orig   = '<p>Bla Bla   Bla</p><pre>Bla Bla  Bla</pre>';
        $expect = '<p>Bla Bla Bla</p>' . "\n" . '<pre>Bla Bla  Bla</pre>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testLigatures(): void
    {
        $orig   = '<p>ﬁ is fi and ﬂ is fl</p>';
        $expect = '<p>fi is fi and fl is fl</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
        $this->assertNotEquals($out, $orig);
    }

    public function testStripTabs(): void
    {
        $orig   = '<ul><li>
	<p><ins>Test 1<br />
	Test 2.</ins></p></li></ul>';
        $expect = '<ul>
<li>
<p>Test 1<br>
Test 2.</p>
</li>
</ul>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testUmlautDomains(): void
    {
        $orig   = '<p>Test <a href="http://www.hössl.org">My Domain</a></p>';
        $expect = '<p>Test <a href="http://www.xn--hssl-5qa.org">My Domain</a></p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testStripDelTags(): void
    {
        $orig   = '<p>Test <del>this should <strong>be</strong> deleted</del> more <del>this as well</del>.</p>';
        $expect = '<p>Test more .</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testDontStripInsTags(): void
    {
        $orig   = '<p>Test <ins>this should <strong>be</strong> inserted</ins> more <ins>this as well</ins>.</p>';
        $expect = '<p>Test this should <strong>be</strong> inserted more this as well.</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    public function testNormalizeUmlauts(): void
    {
        $orig   = '<p>' . chr(195) . chr(164) . chr(97) . chr(204) . chr(136) . '</p>';
        $expect = '<p>ää</p>';
        $this->assertNotEquals($expect, $orig);

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }
}
