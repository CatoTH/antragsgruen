<?php

namespace app\components;

use yii\helpers\HtmlPurifier;

class HTMLTools
{
    /**
     * @param string $html
     * @return string
     */
    public static function cleanTrustedHtml($html)
    {
        $html = str_replace(chr(194) . chr(160), " ", $html);
        // @TODO
        return $html;
    }



    /**
     * @param string $html
     * @return string
     */
    public static function cleanUntrustedHtml($html)
    {
        $html = str_replace(chr(194) . chr(160), " ", $html);
        // @TODO
        return $html;
    }



    /**
     * @param string $html
     * @return string
     */
    public static function cleanSimpleHtml($html)
    {
        $html = str_replace(chr(194) . chr(160), " ", $html);

        $html = HtmlPurifier::process(
            $html,
            [
                'HTML.Doctype'                            => 'HTML 4.01 Transitional',
                'HTML.AllowedElements'                    => 'p,strong,em,ul,ol,li,s,span,a,br',
                'HTML.AllowedAttributes'                  => 'style,href',
                'CSS.AllowedProperties'                   => 'text-decoration',
                'AutoFormat.Linkify'                      => true,
                'AutoFormat.AutoParagraph'                => false,
                'AutoFormat.RemoveSpansWithoutAttributes' => true,
                'AutoFormat.RemoveEmpty'                  => true,
                'Core.NormalizeNewlines'                  => true,
                'Core.AllowHostnameUnderscore'            => true,
                'Core.EnableIDNA'                         => true,
                'Output.SortAttr'                         => true,
                'Output.Newline'                          => "\n",
            ]
        );

        $html = str_ireplace("</li>", "</li>\n", $html);
        $html = str_ireplace("<ul>", "<ul>\n", $html);
        $html = str_ireplace("</ul>", "</ul>\n", $html);
        $html = str_ireplace("</p>", "</p>\n", $html);
        $html = str_ireplace("<br>", "<br>\n", $html);

        $html = preg_replace("/\\n+/siu", "\n", $html);

        return $html;
    }


}
