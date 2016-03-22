<?php

namespace app\components;

use app\models\exceptions\Internal;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

class HTMLTools
{
    public static $KNOWN_BLOCK_ELEMENTS = ['div', 'ul', 'li', 'ol', 'blockquote', 'pre', 'p', 'section'];

    /**
     * @param string $html
     * @return string
     */
    public static function cleanMessedUpHtmlCharacters($html)
    {
        $html = str_replace(chr(194) . chr(160), ' ', $html); // Long space
        $html = str_replace(chr(0xef) . chr(0xbb) . chr(0xbf), '', $html); // Byte order Mark

        // Replace multiple spaces by one, except within <pre>
        $html = preg_replace_callback('/<pre>.*<\/pre>/siuU', function ($matches) {
            return str_replace(' ', chr(194) . chr(160), $matches[0]);
        }, $html);
        $html = str_replace("\n\t", "\n", $html);
        $html = str_replace("\t", ' ', $html);
        $html = preg_replace('/ {2,}/siu', ' ', $html);
        $html = str_replace(chr(194) . chr(160), ' ', $html);

        // Ligature characters
        $html = str_replace(['ﬁ', 'ﬂ', 'ﬀ', 'ﬃ', 'ﬄ', 'ﬆ'], ['fi', 'fl', 'ff', 'ffi', 'ffl', 'st'], $html);

        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    public static function cleanTrustedHtml($html)
    {
        $html = static::cleanMessedUpHtmlCharacters($html);
        $html = str_replace("\r", '', $html);
        // @TODO
        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    public static function correctHtmlErrors($html)
    {
        $str = HtmlPurifier::process(
            $html,
            function ($config) {
                /** @var \HTMLPurifier_Config $config */
                $conf = [
                    'HTML.Doctype'                            => 'HTML 4.01 Transitional',
                    'HTML.AllowedElements'                    => null,
                    'Attr.AllowedClasses'                     => null,
                    'CSS.AllowedProperties'                   => null,
                    'AutoFormat.Linkify'                      => true,
                    'AutoFormat.AutoParagraph'                => false,
                    'AutoFormat.RemoveSpansWithoutAttributes' => false,
                    'AutoFormat.RemoveEmpty'                  => false,
                    'Core.NormalizeNewlines'                  => false,
                    'Core.AllowHostnameUnderscore'            => true,
                    'Core.EnableIDNA'                         => true,
                    'Output.SortAttr'                         => true,
                    'Output.Newline'                          => "\n"
                ];
                foreach ($conf as $key => $val) {
                    $config->set($key, $val);
                }
            }
        );
        $str = static::cleanMessedUpHtmlCharacters($str);
        return $str;
    }


    /**
     * @param string $html
     * @return string
     */
    public static function cleanSimpleHtml($html)
    {
        $html = str_replace("\r", '', $html);

        $html = HtmlPurifier::process(
            $html,
            function ($config) {
                $allowedTags = 'p,strong,em,ul,ol,li,span,a,br,blockquote,sub,sup,pre';
                /** @var \HTMLPurifier_Config $config */
                $conf = [
                    'HTML.Doctype'                            => 'HTML 4.01 Transitional',
                    'HTML.AllowedElements'                    => $allowedTags,
                    'HTML.AllowedAttributes'                  => 'style,href,class',
                    'Attr.AllowedClasses'                     => 'underline,strike,subscript,superscript',
                    'CSS.AllowedProperties'                   => '',
                    'AutoFormat.Linkify'                      => true,
                    'AutoFormat.AutoParagraph'                => false,
                    'AutoFormat.RemoveSpansWithoutAttributes' => true,
                    'AutoFormat.RemoveEmpty'                  => true,
                    'Core.NormalizeNewlines'                  => true,
                    'Core.AllowHostnameUnderscore'            => true,
                    'Core.EnableIDNA'                         => true,
                    'Output.SortAttr'                         => true,
                    'Output.Newline'                          => "\n"
                ];
                foreach ($conf as $key => $val) {
                    $config->set($key, $val);
                }
            }
        );

        $html = str_ireplace("</li>", "</li>\n", $html);
        $html = str_ireplace("<ul>", "<ul>\n", $html);
        $html = str_ireplace("</ul>", "</ul>\n", $html);
        $html = str_ireplace("</p>", "</p>\n", $html);
        $html = str_ireplace("<br>", "<br>\n", $html);

        $html = preg_replace("/\\n+/siu", "\n", $html);
        $html = str_replace("<p><br>\n", "<p>", $html);
        $html = str_replace("<br>\n</p>", "</p>", $html);
        $html = str_replace('&nbsp;', ' ', $html);

        $html = static::cleanMessedUpHtmlCharacters($html);
        $html = preg_replace('/<p> +/siu', '<p>', $html);
        $html = preg_replace('/ +<\/p>/siu', '</p>', $html);
        $html = preg_replace('/ +<br>/siu', '<br>', $html);

        $html = trim($html);

        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    public static function prepareHTMLForCkeditor($html)
    {
        $html = preg_replace('/(<[^\/][^>]*>) (\w)/siu', '\\1&nbsp;\\2', $html);
        $html = preg_replace('/(\w) (<\/[^>]*>)/siu', '\\1&nbsp;\\2', $html);
        return $html;
    }

    /**
     * @param \DOMElement $element
     * @param bool $split
     * @param bool $splitListItems
     * @param string $pre
     * @param string $post
     * @return \string[]
     * @throws Internal
     */
    private static function sectionSimpleHTMLInt(\DOMElement $element, $split, $splitListItems, $pre, $post)
    {
        $inlineElements = ['strong', 'em', 'span', 'a', 's', 'u', 'i', 'b', 'sub', 'sup'];
        if (!$splitListItems) {
            $inlineElements[] = 'li';
        }
        if (in_array($element->nodeName, ['p', 'li', 'pre'])) {
            $split = false;
        }
        $return        = [];
        $children      = $element->childNodes;
        $pendingInline = null;
        $lino          = 0;
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
                    } elseif (in_array($child->nodeName, $inlineElements) || !$split) {
                        if ($child->nodeName == 'a') {
                            $href = ($child->hasAttribute('href') ? $child->getAttribute('href') : '');
                            if ($child->hasAttribute('class')) {
                                $newPre = '<a href="' . Html::encode($href) . '" ' .
                                    'class="' . Html::encode($child->getAttribute('class')) . '">';
                            } else {
                                $newPre = '<a href="' . Html::encode($href) . '">';
                            }
                        } elseif ($child->nodeName == 'span' && $child->hasAttribute('class')) {
                            $newPre = '<' . $child->nodeName . ' ' .
                                'class="' . Html::encode($child->getAttribute('class')) . '">';
                        } else {
                            $newPre = '<' . $child->nodeName . '>';
                        }
                        $newPost = '</' . $child->nodeName . '>';
                        $newArrs = static::sectionSimpleHTMLInt($child, $split, $splitListItems, $newPre, $newPost);
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
                        if ($child->nodeName == 'ol') {
                            $newPre  = $pre . '<' . $child->nodeName . ' start="#LINO#">';
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = static::sectionSimpleHTMLInt($child, $split, $splitListItems, $newPre, $newPost);
                            $return  = array_merge($return, $newArrs);
                        } elseif ($child->nodeName == 'li') {
                            $lino++;
                            $newPre  = str_replace('#LINO#', $lino, $pre) . '<' . $child->nodeName . '>';
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = static::sectionSimpleHTMLInt($child, $split, $splitListItems, $newPre, $newPost);
                            $return  = array_merge($return, $newArrs);
                        } elseif (in_array($child->nodeName, ['ul', 'blockquote', 'p', 'pre'])) {
                            $newPre  = $pre . '<' . $child->nodeName . '>';
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = static::sectionSimpleHTMLInt($child, $split, $splitListItems, $newPre, $newPost);
                            $return  = array_merge($return, $newArrs);
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
                        $pendingInline .= Html::encode($child->nodeValue);
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
        $return = str_replace("\r", "", $return); // @TODO Array ./. string?
        return $return;
    }

    /**
     * @param string $html
     * @return \DOMNode
     */
    public static function html2DOM($html)
    {
        $html = HtmlPurifier::process(
            $html,
            [
                'HTML.Doctype' => 'HTML 4.01 Transitional',
                'HTML.Trusted' => true,
                'CSS.Trusted'  => true,
            ]
        );

        $src_doc = new \DOMDocument();
        $src_doc->loadHTML('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head><body>' . $html . "</body></html>");
        $bodies = $src_doc->getElementsByTagName('body');

        return $bodies->item(0);
    }


    /**
     * Splits HTML into paragraphs
     * Principles:
     * - Root level lists are split into single list items, if $splitListItems == true. Nested lists are never split
     * - Root level paragraphs are returned as paragraphs
     * - Blockquotes can be split into paragraphs, if multiple P elements are contained
     *
     * @param string $html
     * @param bool $splitListItems
     * @return \string[]
     * @throws Internal
     */
    public static function sectionSimpleHTML($html, $splitListItems = true)
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
        return static::sectionSimpleHTMLInt($body, true, $splitListItems, '', '');
    }


    private static $LINKS;
    private static $LINK_CACHE;

    /**
     * @param string $html
     * @param bool $linksAtEnd
     * @return string
     */
    public static function toPlainText($html, $linksAtEnd = false)
    {
        $html = str_replace("\n", "", $html);
        $html = str_replace("\r", "", $html);
        $html = str_replace(" />", ">", $html);

        static::$LINKS      = $linksAtEnd;
        static::$LINK_CACHE = [];

        $text = str_ireplace("<br>", "\n", $html);
        $text = preg_replace("/<img.*>/siU", "", $text);


        $text = preg_replace_callback("/<ul.*>(.*)<\/ul>/siU", function ($matches) {
            $text = "\n" . preg_replace_callback("/<li.*>(.*)<\/li>/siU", function ($matches2) {
                    $text = "* " . $matches2[1] . "\n";
                    return $text;
                }, $matches[1]);
            return $text;
        }, $text);

        $text = preg_replace_callback("/<ol.*>(.*)<\/ol>/siU", function ($matches) {
            $text = "\n" . preg_replace_callback("/<li.*>(.*)<\/li>/siU", function ($matches2) {
                    $text = "* " . $matches2[1] . "\n";
                    return $text;
                }, $matches[1]);
            return $text;
        }, $text);

        $text = preg_replace_callback("/<a.*href=[\"'](.*)[\"'].*>(.*)<\/a>/siU", function ($matches) {
            $begr = trim($matches[2]);
            if ($begr == '') {
                return '';
            }

            if (static::$LINKS) {
                $newnr                      = count(static::$LINK_CACHE) + 1;
                static::$LINK_CACHE[$newnr] = $matches[1];
                return $begr . " [$newnr]";
            } else {
                return $begr . " ($matches[1])";
            }
        }, $text);


        $text = preg_replace_callback("/<i>(.*)<\/i>/siU", function ($matches) {
            $text = "/" . $matches[1] . "/";
            return $text;
        }, $text);

        $text = preg_replace_callback("/<ins[^>]*>(.*)<\/ins>/siU", function ($matches) {
            $ins = \Yii::t('diff', 'plain_text_ins');
            $text = '[' . $ins . ']' . $matches[1] . '[/' . $ins . ']';
            return $text;
        }, $text);

        $text = preg_replace_callback("/<del[^>]*>(.*)<\/del>/siU", function ($matches) {
            $ins = \Yii::t('diff', 'plain_text_del');
            $text = '[' . $ins . ']' . $matches[1] . '[/' . $ins . ']';
            return $text;
        }, $text);

        $text = str_ireplace("</tr>", "\n", $text);

        $appendLineBr = function ($matches) {
            $text = $matches[1];
            if ($matches[1] != "\n" && $matches[1] != ">" && $matches[1] != "") {
                $text .= "\n";
            }
            $text .= $matches[2];
            if (isset($matches[3]) && $matches[3] != "\n" && $matches[3] != "") {
                $text .= "\n";
            }
            if (isset($matches[3])) {
                $text .= $matches[3];
            }
            return $text;
        };

        $textOld = '';
        while ($text != $textOld) {
            $textOld = $text;
            $text    = preg_replace_callback("/(.)?<div.*>(.*)<\/div>(.)/siU", $appendLineBr, $text);
            $text    = preg_replace_callback("/(.)?<p.*>(.*)<\/p>(.)/siU", $appendLineBr, $text);
            $text    = preg_replace_callback("/(.)?<h1.*>(.*)<\/h1>(.)/siU", $appendLineBr, $text);
            $text    = preg_replace_callback("/(.)?<h2.*>(.*)<\/h2>(.)/siU", $appendLineBr, $text);
            $text    = preg_replace_callback("/(.)?<h3.*>(.*)<\/h3>(.)/siU", $appendLineBr, $text);
            $text    = preg_replace_callback("/(.)?<h4.*>(.*)<\/h4>(.)/siU", $appendLineBr, $text);
        }

        $text = strip_tags($text);

        $text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');

        if ($linksAtEnd && count(static::$LINK_CACHE) > 0) {
            $text .= "\n\n\nLinks:\n";
            foreach (static::$LINK_CACHE as $nr => $link) {
                $text .= "[$nr] $link\n";
            }
        }

        return trim($text);
    }

    /**
     * @param string $formName
     * @param array $options
     * @param string $selected
     * @param array $attributes
     * @return string
     */
    public static function fueluxSelectbox($formName, $options, $selected = '', $attributes = [])
    {
        $str = '<div class="btn-group selectlist" data-resize="auto" data-initialize="selectlist"';
        foreach ($attributes as $attrName => $attrVal) {
            $str .= ' ' . $attrName . '="' . Html::encode($attrVal) . '"';
        }
        $str .= '>
  <button class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button">
    <span class="selected-label"></span>
    <span class="caret"></span>
    <span class="sr-only">Toggle Dropdown</span>
  </button>
  <ul class="dropdown-menu" role="menu">';
        foreach ($options as $value => $name) {
            $str .= '<li data-value="' . Html::encode($value) . '" ';
            if ($value == $selected) {
                $str .= ' data-selected="true"';
            }
            $str .= '><a href="#">' . Html::encode($name) . '</a></li>';
        }
        $str .= '</ul>
  <input class="hidden hidden-field" name="' . $formName . '" readonly="readonly" ' .
            ' title="[Hidden]" aria-hidden="true" type="text" value="' . Html::encode($selected) . '">
</div>';
        return $str;
    }

    /**
     * @param string $formName
     * @param array $options
     * @param string $value
     * @return string
     */
    public static function smallTextarea($formName, $options, $value = '')
    {
        $rows = count(explode("\n", $value));
        if (isset($options['placeholder'])) {
            $rows2 = count(explode("\n", $options['placeholder']));
            if ($rows2 > $rows) {
                $rows = $rows2;
            }
        }
        $str = '<textarea name="' . Html::encode($formName) . '" rows="' . $rows . '"';
        foreach ($options as $key => $val) {
            $str .= ' ' . $key . '="' . Html::encode($val) . '"';
        }
        $str .= '>' . $value . '</textarea>';
        return $str;
    }

    /**
     * @param \DOMNode $node
     * @return string
     */
    public static function renderDomToHtml(\DOMNode $node)
    {
        if (is_a($node, \DOMElement::class)) {
            if ($node->nodeName == 'br') {
                return '<br>';
            }
            /** @var \DOMElement $node */
            $str = '<' . $node->nodeName;
            foreach ($node->attributes as $key => $val) {
                $val = $node->getAttribute($key);
                $str .= ' ' . $key . '="' . Html::encode($val) . '"';
            }
            $str .= '>';
            foreach ($node->childNodes as $child) {
                $str .= static::renderDomToHtml($child);
            }
            $str .= '</' . $node->nodeName . '>';
            return $str;
        } else {
            /** @var \DOMText $node */
            return Html::encode($node->data);
        }
    }

    /**
     * @param \DOMNode $node
     * @return array
     */
    public static function getDomDebug(\DOMNode $node)
    {
        if (is_a($node, \DOMElement::class)) {
            /** @var \DOMNode $node */
            $node = [
                'name'     => $node->nodeName,
                'classes'  => '',
                'children' => [],
            ];
            foreach ($node->childNodes as $child) {
                $node['children'][] = static::getDomDebug($child);
            }
            return $node;
        } else {
            /** @var \DOMText $node */
            return [
                'text' => $node->data,
            ];
        }
    }

    /**
     * @param $text
     * @return string
     */
    public static function textToHtmlWithLink($text)
    {
        $html = nl2br(Html::encode($text), false);

        $urlsearch = $urlreplace = [];
        $wwwsearch = $wwwreplace = [];

        $urlMaxlen      = 250;
        $urlMaxlenEnd   = 50;
        $urlMaxlenHost  = 150;
        $urlPatternHost = '[-a-zäöüß0-9\_\.]';
        $urlPattern     = '([-a-zäöüß0-9\_\$\.\:;\/?=\+\~@,%#!\'\[\]\|]|\&(?!amp\;|lt\;|gt\;|quot\;)|\&amp\;)';
        $urlPatternEnd  = '([-a-zäöüß0-9\_\$\:\/=\+\~@%#\|]|\&(?!amp\;|lt\;|gt\;|quot\;)|\&amp\;)';

        $endPattern     = "($urlPatternEnd|($urlPattern*\\($urlPattern{0,$urlMaxlenEnd}\\)){1,3})";
        $hostUrlPattern = "$urlPatternHost{1,$urlMaxlenHost}(\\/?($urlPattern{0,$urlMaxlen}$endPattern)?)?";

        $urlsearch[]  = "/([({\\[\\|>\\s])((https?|ftp|news):\\/\\/|mailto:)($hostUrlPattern)/siu";
        $urlreplace[] = "\\1<a rel=\"nofollow\" href=\"\\2\\4\">\\2\\4</a>";

        $urlsearch[]  = "/^((https?|ftp|news):\\/\\/|mailto:)($hostUrlPattern)/siu";
        $urlreplace[] = "<a rel=\"nofollow\" href=\"\\1\\3\">\\1\\3</a>";

        $wwwsearch[]  = "/([({\\[\\|>\\s])((?<![\\/\\/])www\\.)($hostUrlPattern)/siu";
        $wwwreplace[] = "\\1<a rel=\"nofollow\" href=\"http://\\2\\3\">\\2\\3</a>";

        $wwwsearch[]  = "/^((?<![\\/\\/])www\\.)($hostUrlPattern)/siu";
        $wwwreplace[] = "<a rel=\"nofollow\" href=\"http://\\1\\2\">\\1\\2</a>";

        $html = preg_replace($urlsearch, $urlreplace, $html);
        $html = preg_replace($wwwsearch, $wwwreplace, $html);

        return $html;
    }
}
