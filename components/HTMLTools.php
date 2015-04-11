<?php

namespace app\components;

use app\models\exceptions\Internal;
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
                'HTML.AllowedElements'                    => 'p,strong,em,ul,ol,li,s,span,a,br,blockquote',
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

    /**
     * @param \DOMElement $element
     * @param string $pre
     * @param string $post
     * @return \string[]
     * @throws Internal
     */
    private static function sectionSimpleHTMLInt(\DOMElement $element, $pre, $post)
    {
        // @TODO A HREF, Underline
        $return        = [];
        $children      = $element->childNodes;
        $pendingInline = null;
        for ($i = 0; $i < $children->length; $i++) {
            $child = $children->item($i);
            switch (get_class($child)) {
                case 'DOMElement':
                    /** @var \DOMElement $child */
                    if ($child->nodeName == 'br') {
                        if ($pendingInline === null) {
                            $pendingInline = '';
                        }
                        $pendingInline .= '<br>';
                    } elseif (in_array($child->nodeName, ['strong', 'em', 's'])) {
                        $newPre  = '<' . $child->nodeName . '>';
                        $newPost = '</' . $child->nodeName . '>';
                        $newArrs = static::sectionSimpleHTMLInt($child, $newPre, $newPost);
                        if ($pendingInline === null) {
                            $pendingInline = '';
                        }
                        foreach ($newArrs as $str) {
                            $pendingInline .= $str;
                        }
                    } else {
                        if ($pendingInline !== null) {
                            $return[]      = $pre . $pendingInline . $post;
                            $pendingInline = null;
                        }
                        if (in_array($child->nodeName, ['p', 'ul', 'ol', 'li', 'blockquote'])) {
                            $newPre  = $pre . '<' . $child->nodeName . '>';
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = static::sectionSimpleHTMLInt($child, $newPre, $newPost);
                            $return = array_merge($return, $newArrs);
                        } else {
                            throw new Internal('Unknown Tag: ' . $child->nodeName);
                        }
                    }
                    break;
                case 'DOMText':
                    if (trim($child->nodeValue) != '') {
                        if ($pendingInline === null) {
                            $pendingInline = '';
                        }
                        $pendingInline .= $child->nodeValue;
                    }
                    break;
                default:
                    var_dump($child);
                    die();
            }
        }
        if ($pendingInline !== null) {
            $return[]      = $pre . $pendingInline . $post;
            $pendingInline = null;
        }

        return $return;
    }


    /**
     * @param string $html
     * @return string[]
     */
    public static function sectionSimpleHTML($html)
    {
        $src_doc = new \DOMDocument();
        $src_doc->loadHTML(
            '<html><head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            </head><body>' . $html . '</body></html>'
        );
        $bodies = $src_doc->getElementsByTagName('body');
        $body   = $bodies->item(0);

        /** @var \DOMElement $body */
        return static::sectionSimpleHTMLInt($body, '', '');
    }
}
