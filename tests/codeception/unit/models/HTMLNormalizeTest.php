<?php

namespace tests\codeception\unit\models;

use app\components\Tools;
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

                $out = Tools::cleanSimpleHtml($in);
                $this->assertEquals($out, $expect);
            }
        );
    }
    /*

    public function testLoginNoUser()
    {
        $model = new LoginForm([
            'username' => 'not_existing_username',
            'password' => 'not_existing_password',
        ]);

        $this->specify('user should not be able to login, when there is no identity', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('user should not be logged in', Yii::$app->user->isGuest)->true();
        });
    }

    public function testLoginWrongPassword()
    {
        $model = new LoginForm([
            'username' => 'demo',
            'password' => 'wrong_password',
        ]);

        $this->specify('user should not be able to login with wrong password', function () use ($model) {
            expect('model should not login user', $model->login())->false();
            expect('error message should be set', $model->errors)->hasKey('password');
            expect('user should not be logged in', Yii::$app->user->isGuest)->true();
        });
    }

    public function testLoginCorrect()
    {
        $model = new LoginForm([
            'username' => 'demo',
            'password' => 'demo',
        ]);

        $this->specify('user should be able to login with correct credentials', function () use ($model) {
            expect('model should login user', $model->login())->true();
            expect('error message should not be set', $model->errors)->hasntKey('password');
            expect('user should be logged in', Yii::$app->user->isGuest)->false();
        });
    }
    */
}
