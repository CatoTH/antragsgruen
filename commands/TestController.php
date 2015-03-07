<?php
namespace app\commands;

use yii\console\Controller;
use yii\helpers\HtmlPurifier;

/**
 * Commands not needed for production environments but for testing miscellaneous stuff
 * @package app\commands
 */
class TestController extends Controller
{
    /**
     * @throws \yii\db\Exception
     */
    public function actionPurify()
    {
        $input  = '<p style="font-weight: bold;">Test <strong>Bold</strong> <em>italic</em> <strike>Strike1</strike> ' .
            '<!-- Comment -->' .
            '<u>Unterline</u> <span style="text-decoration: line-through;">Strike 2</span><br>' .
            'New line</p><ul><li>Test1</li><li>Test 2</li></ul>';
        $output = HtmlPurifier::process(
            $input,
            [
                'HTML.AllowedElements'                    => 'p,strong,em,ul,ol,li,strike,span,a',
                'HTML.AllowedAttributes'                  => 'style,href',
                'CSS.AllowedProperties'                   => 'text-decoration',
                'AutoFormat.Linkify'                      => true,
                'AutoFormat.AutoParagraph'                => true,
                'AutoFormat.RemoveSpansWithoutAttributes' => true,
                'AutoFormat.RemoveEmpty'                  => true,
                'Core.NormalizeNewlines'                  => true,
                'Core.AllowHostnameUnderscore'            => true,
                'Core.EnableIDNA'                         => true,
                'Output.SortAttr'                         => true,
                'Output.Newline'                          => "\n",
            ]
        );
        echo $output . "\n";
    }
}
