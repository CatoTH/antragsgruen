<?php

namespace tests\codeception\unit\models;

use app\components\HTMLTools;
use Yii;
use yii\codeception\TestCase;
use Codeception\Specify;

class HTMLNormalizeTest extends TestCase
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
                $expect .= "<li>Test2\n<s>Test3</s></li>\n</ul>\n";

                $in .= "<a href='http://www.example.org/'>Example</a><u>Underlined</u>";
                $expect .= "<a href=\"http://www.example.org/\">Example</a>Underlined";

                $in .= "<!-- Comment -->";
                $expect .= "";

                $out = HTMLTools::cleanSimpleHtml($in);
                $this->assertEquals($out, $expect);
            }
        );
    }
}
