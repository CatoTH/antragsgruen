<?php

namespace unit;

use app\components\HTMLTools;
use Yii;
use Codeception\Specify;
use Codeception\Util\Autoload;

Autoload::addNamespace('unit', __DIR__);

class HTMLNormalizeTest extends TestBase
{
    use Specify;

    /**
     *
     */
    public function testParagraphs()
    {
        $this->specify(
            'Creating Paragraphs',
            function () {
                $in  = "<p>Test<br><span style='color: red;'>Test2</span><br><span></span>";
                $expect = "<p>Test<br>\nTest2<br>\n";

                $in .= "<strong onClick=\"alert('Alarm!');\">Test3</strong><br><br>\r\r";
                $expect .= "<strong>Test3</strong><br>\n<br>\n";

                $in .= "Test4</p><ul><li>Test</li>\r";
                $expect .= "Test4</p>\n<ul>\n<li>Test</li>\n";

                $in .= "<li>Test2\n<s>Test3</s></li>\r\n\r\n</ul>";
                $expect .= "<li>Test2\nTest3</li>\n</ul>\n";

                $in .= "<a href='http://www.example.org/'>Example</a><u>Underlined</u>";
                $expect .= "<a href=\"http://www.example.org/\">Example</a>Underlined";

                $in .= "<!-- Comment -->";
                $expect .= "";

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Underlined allowed',
            function () {
                $in  = "<span class='underline'>Underlined</span> Normal";
                $expect = '<span class="underline">Underlined</span> Normal';

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Strike allowed',
            function () {
                $in  = "<span class='strike'>Strike</span> Normal";
                $expect = '<span class="strike">Strike</span> Normal';

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Subscript allowed',
            function () {
                $in  = "<span class='subscript'>Subscript</span> Normal";
                $expect = '<span class="subscript">Subscript</span> Normal';

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Superscript allowed',
            function () {
                $in  = "<span class='superscript'>Superscript</span> Normal";
                $expect = '<span class="superscript">Superscript</span> Normal';

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($expect, $out);
            }
        );

        $this->specify(
            'Strip unknown classes',
            function () {
                $in  = "<span class='unknown'>Strike</span> Normal";
                $expect = 'Strike Normal';

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($expect, $out);


                $in  = "<span class='strike unknown'>Strike</span> Normal";
                $expect = '<span class="strike">Strike</span> Normal';

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($expect, $out);
            }
        );
    }
}
