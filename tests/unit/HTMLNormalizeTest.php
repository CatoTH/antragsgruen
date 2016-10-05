<?php

namespace unit;

use app\components\HTMLTools;
use Yii;
use Codeception\Specify;

class HTMLNormalizeTest extends TestBase
{
    use Specify;


    /**
     */
    public function testStripEmptySpans()
    {
        $orig   = '<p>Test</p><p><span class="underlined"><span>&nbsp;</span></span></p>';
        $expect = '<p>Test</p>' . "\n" . '<p></p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $out = HTMLTools::cleanSimpleHtml($out);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testStripEmptyAs()
    {
        $orig   = '<h2><a>Test</a> - <a href="https://antragsgruen.de">Test 2</a></h2>';
        $expect = '<h2>Test - <a href="https://antragsgruen.de">Test 2</a></h2>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $out = HTMLTools::cleanSimpleHtml($out);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testDanglingNbsp()
    {
        $orig   = '<p><span>so ein Fahrradanteil von 30 % und mehr erreicht werden kann.&nbsp;</span></p>';
        $expect = '<p>so ein Fahrradanteil von 30 % und mehr erreicht werden kann.</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testStripLeadingSpaces()
    {
        $orig   = '<p> Test 123 </p>';
        $expect = '<p>Test 123</p>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testPrepareForCKEditor()
    {
        $orig   = '<p><strong> Test</strong></p> Test2';
        $expect = '<p><strong>&nbsp;Test</strong></p> Test2';
        $out    = HTMLTools::prepareHTMLForCkeditor($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testCreateParagraphs()
    {
        $orig   = "<p>Test<br><span style='color: red;'>Test2</span><br><span></span>";
        $expect = "<p>Test<br>\nTest2<br>\n";

        $orig .= "<strong onClick=\"alert('Alarm!');\">Test3</strong><br><br>\r\r";
        $expect .= "<strong>Test3</strong><br>\n<br>\n";

        $orig .= "Test4</p><ul><li>Test</li>\r";
        $expect .= "Test4</p>\n<ul>\n<li>Test</li>\n";

        $orig .= "<li>Test2\n<s>Test3</s></li>\r\n\r\n</ul>";
        $expect .= "<li>Test2\nTest3</li>\n</ul>\n";

        $orig .= "<a href='http://www.example.org/'>Example</a><u>Underlined</u>";
        $expect .= "<a href=\"http://www.example.org/\">Example</a>Underlined";

        $orig .= "<!-- Comment -->";
        $expect .= "";

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testAllowUnderlines()
    {
        $orig   = "<span class='underline'>Underlined</span> Normal";
        $expect = '<span class="underline">Underlined</span> Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testAllowH2H3H4()
    {
        $orig   = '<h1>H1</h1><h2>H2</h2><h3>H3</h3><h4>H4</h4>';
        $expect = 'H1<h2>H2</h2><h3>H3</h3><h4>H4</h4>';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testAllowStrike()
    {
        $orig   = "<span class='strike'>Strike</span> Normal";
        $expect = '<span class="strike">Strike</span> Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testAllowSubscript()
    {
        $orig   = "<span class='subscript'>Subscript</span> Normal";
        $expect = '<span class="subscript">Subscript</span> Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $orig   = "<sub>Subscript</sub> Normal";
        $expect = '<sub>Subscript</sub> Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testSuperscript()
    {
        $orig   = "<span class='superscript'>Superscript</span> Normal";
        $expect = '<span class="superscript">Superscript</span> Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

        $orig   = "<sup>Superscript</sup> Normal";
        $expect = '<sup>Superscript</sup> Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testStripUnknown()
    {
        $orig   = "<span class='unknown'>Strike</span> Normal";
        $expect = 'Strike Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);


        $orig   = "<span class='strike unknown'>Strike</span> Normal";
        $expect = '<span class="strike">Strike</span> Normal';

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testStripBRsAtBeginningAndEnd()
    {
        $orig   = "<p>Test1</p>\n<p><br>\nTest2<br></p>";
        $expect = "<p>Test1</p>\n<p>Test2</p>";
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testStripWhitespacesAtEnd()
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

    /**
     */
    public function testStripMultipleSpaces()
    {
        $orig   = '<p>Bla Bla   Bla</p><pre>Bla Bla  Bla</pre>';
        $expect = '<p>Bla Bla Bla</p>' . "\n" . '<pre>Bla Bla  Bla</pre>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testLigatures()
    {
        $orig   = 'ﬁ is fi and ﬂ is fl';
        $expect = 'fi is fi and fl is fl';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
        $this->assertNotEquals($out, $orig);
    }

    /**
     */
    public function testStripTabs()
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

    /**
     */
    public function testUmlautDomains()
    {
        $orig   = 'Test <a href="http://www.hössl.org">My Domain</a>';
        $expect = 'Test <a href="http://www.xn--hssl-5qa.org">My Domain</a>';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testStripDelTags()
    {
        $orig   = 'Test <del>this should <strong>be</strong> deleted</del> more <del>this as well</del>.';
        $expect = 'Test more .';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);

    }

    /**
     */
    public function testDontStripInsTags()
    {
        $orig   = 'Test <ins>this should <strong>be</strong> inserted</ins> more <ins>this as well</ins>.';
        $expect = 'Test this should <strong>be</strong> inserted more this as well.';
        $out    = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }

    /**
     */
    public function testNormalizeUmlauts()
    {
        $orig   = chr(195) . chr(164) . chr(97) . chr(204) . chr(136);
        $expect = 'ää';
        $this->assertNotEquals($expect, $orig);

        $out = HTMLTools::cleanSimpleHtml($orig);
        $this->assertEquals($expect, $out);
    }
}
