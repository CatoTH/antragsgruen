<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\exceptions\Internal;
use yii\helpers\Html;
use yii\helpers\HtmlPurifier;

class HTMLTools
{
    public static $KNOWN_BLOCK_ELEMENTS = ['div', 'ul', 'li', 'ol', 'blockquote', 'pre', 'p', 'section',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    /**
     * @param string $str
     * @return bool
     */
    public static function isStringCachable($str)
    {
        return strlen($str) > 1000;
    }

    /**
     * Required by HTML Purifier to handle Umlaut domains
     */
    public static function loadNetIdna2()
    {
        $dir  = __DIR__ . DIRECTORY_SEPARATOR . 'Net_IDNA2-0.1.1' . DIRECTORY_SEPARATOR . 'Net' . DIRECTORY_SEPARATOR;
        $dir2 = $dir . 'IDNA2' . DIRECTORY_SEPARATOR;
        @require_once $dir2 . 'Exception.php';
        @require_once $dir2 . 'Exception' . DIRECTORY_SEPARATOR . 'Nameprep.php';
        @require_once $dir . 'IDNA2.php';
    }

    /**
     * @param string $html
     * @return string
     */
    public static function cleanMessedUpHtmlCharacters($html)
    {
        if (function_exists('normalizer_normalize')) {
            $html = normalizer_normalize($html);
        }

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
     * @param string $htmlIn
     * @param bool $linkify
     * @return string
     */
    public static function correctHtmlErrors($htmlIn, $linkify = false)
    {
        $cacheKey = 'correctHtmlErrors_' . md5($htmlIn);
        if (static::isStringCachable($htmlIn) && \Yii::$app->getCache()->exists($cacheKey)) {
            return \Yii::$app->getCache()->get($cacheKey);
        }

        static::loadNetIdna2();
        $str = HtmlPurifier::process(
            $htmlIn,
            function ($config) use ($linkify) {
                /** @var \HTMLPurifier_Config $config */
                $conf = [
                    'HTML.Doctype'                            => 'HTML 4.01 Transitional',
                    'HTML.AllowedElements'                    => null,
                    'Attr.AllowedClasses'                     => null,
                    'CSS.AllowedProperties'                   => null,
                    'AutoFormat.Linkify'                      => $linkify,
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
                $def                                                    = $config->getHTMLDefinition(true);
                $def->info_global_attr['data-moving-partner-id']        = new \HTMLPurifier_AttrDef_Text();
                $def->info_global_attr['data-moving-partner-paragraph'] = new \HTMLPurifier_AttrDef_Text();
            }
        );
        $str = static::cleanMessedUpHtmlCharacters($str);
        if (static::isStringCachable($htmlIn)) {
            \Yii::$app->getCache()->set($cacheKey, $str);
        }

        return $str;
    }

    /**
     * @param string $html
     * @return string
     */
    public static function wrapOrphanedTextWithP($html)
    {
        $dom = static::html2DOM($html);

        $hasChanged = false;
        /** @var \DOMElement $wrapP */
        $wrapP = null;
        for ($i = 0; $i < $dom->childNodes->length; $i++) {
            $childNode = $dom->childNodes->item($i);
            /** @var \DOMNode $childNode */
            $isText   = is_a($childNode, \DOMText::class);
            $isInline = !in_array($childNode->nodeName, static::$KNOWN_BLOCK_ELEMENTS);
            if ($isText || $isInline) {
                $hasChanged = true;
                if ($wrapP === null) {
                    $wrapP = $dom->ownerDocument->createElement('p');
                }
                $dom->removeChild($childNode);
                $wrapP->appendChild($childNode);
                $i--;
            } else {
                if ($wrapP) {
                    if ($wrapP->childNodes->length > 1 || trim($wrapP->childNodes->item(0)->nodeValue) != '') {
                        $first = $wrapP->childNodes->item(0);
                        $last  = $wrapP->childNodes->item($wrapP->childNodes->length - 1);
                        if (is_a($first, \DOMText::class)) {
                            $first->nodeValue = preg_replace('/^[ \\n]*/', '', $first->nodeValue);
                        }
                        if (is_a($last, \DOMText::class)) {
                            $last->nodeValue = preg_replace('/\s*$/', '', $last->nodeValue);
                        }
                        $dom->insertBefore($wrapP, $childNode);
                    }
                    $wrapP = null;
                }
            }
        }
        if ($wrapP && ($wrapP->childNodes->length > 1 || trim($wrapP->childNodes->item(0)->nodeValue) != '')) {
            $first = $wrapP->childNodes->item(0);
            $last  = $wrapP->childNodes->item($wrapP->childNodes->length - 1);
            if (is_a($first, \DOMText::class)) {
                $first->nodeValue = preg_replace('/^\s*/', '', $first->nodeValue);
            }
            if (is_a($last, \DOMText::class)) {
                $last->nodeValue = preg_replace('/\s*$/', '', $last->nodeValue);
            }
            $dom->appendChild($wrapP);
        }
        if ($hasChanged) {
            return static::renderDomToHtml($dom, true);
        } else {
            return $html;
        }
    }

    /**
     * @param string $htmlIn
     * @param string[] $forbiddenFormattings
     * @return string
     */
    public static function cleanSimpleHtml($htmlIn, $forbiddenFormattings = [])
    {
        $cacheKey = 'cleanSimpleHtml_' . implode(',', $forbiddenFormattings) . '_' . md5($htmlIn);
        if (static::isStringCachable($htmlIn) && \Yii::$app->getCache()->exists($cacheKey)) {
            return \Yii::$app->getCache()->get($cacheKey);
        }

        $html = str_replace("\r", '', $htmlIn);

        // When coming from amendment creating
        // should only happen in some edge cases where the editor was not used correctly
        $html = preg_replace('/<del[^>]*>.*<\/del>/siuU', '', $html);

        // Remove <a>...</a>
        $html = preg_replace('/<a>(.*)<\/a>/siuU', '$1', $html);

        $allowedTags = [
            'p', 'strong', 'em', 'ul', 'ol', 'li', 'span', 'a', 'br', 'blockquote',
            'sub', 'sup', 'pre', 'h1', 'h2', 'h3', 'h4'
        ];

        $allowedClasses = ['underline', 'subscript', 'superscript'];
        if (!in_array('strike', $forbiddenFormattings)) {
            $allowedClasses[] = 'strike';
        }

        $allowedAttributes = ['style', 'href', 'class'];

        static::loadNetIdna2();
        $html = str_replace('<p></p>', '<p>###EMPTY###</p>', $html);
        $html = HtmlPurifier::process(
            $html,
            function ($config) use ($allowedTags, $allowedClasses, $allowedAttributes) {
                /** @var \HTMLPurifier_Config $config */
                $conf = [
                    'HTML.Doctype'                            => 'HTML 4.01 Transitional',
                    'HTML.AllowedElements'                    => implode(',', $allowedTags),
                    'HTML.AllowedAttributes'                  => implode(',', $allowedAttributes),
                    'Attr.AllowedClasses'                     => implode(',', $allowedClasses),
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
        $html = str_replace('<p>###EMPTY###</p>', '<p></p>', $html);

        // Text always needs to be in a block container. This is the normal case anyway,
        // however sometimes CKEditor + Lite Change Tracking produces messed up HTML that we need to fix here
        $html = static::wrapOrphanedTextWithP($html);

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
        $html = preg_replace('/ +<\/li>/siu', '</li>', $html);
        $html = preg_replace('/ +<br>/siu', '<br>', $html);

        $html = trim($html);

        if (static::isStringCachable($htmlIn)) {
            \Yii::$app->getCache()->set($cacheKey, $html);
        }

        return $html;
    }

    /**
     * @param string $html
     * @return string
     */
    public static function stripEmptyBlockParagraphs($html)
    {
        do {
            $htmlPre = $html;
            $html    = preg_replace('/<(p|div|li|ul|ol|h1|h2|h3|h4|h5)>\s*<\/\1>/siu', '', $html);
        } while ($htmlPre != $html);

        $html = preg_replace("/\\n\s*\\n+/siu", "\n", $html);
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
                        } elseif (in_array($child->nodeName,
                            ['ul', 'blockquote', 'p', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])
                        ) {
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
     * @param bool $correctBefore
     * @return \DOMElement
     */
    public static function html2DOM($html, $correctBefore = true)
    {
        if ($correctBefore) {
            $html = static::correctHtmlErrors($html);
        }

        $src_doc = new \DOMDocument();
        $src_doc->loadHTML('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head><body>' . $html . "</body></html>");
        $bodies = $src_doc->getElementsByTagName('body');
        $str    = $bodies->item(0);

        return $str;
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
        $body = static::html2DOM($html);
        return static::sectionSimpleHTMLInt($body, true, $splitListItems, '', '');
    }

    /**
     * Tries to restore the original HTML after re-combining reviously split markup.
     * Currently, this only joins adjacent top-level lists.
     *
     * @param string $html
     * @return string
     */
    public static function removeSectioningFragments($html)
    {
        $body     = static::html2DOM($html);
        $children = $body->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
            $appendToPrev = false;
            $child        = $children->item($i);
            if (is_a($child, \DOMText::class) && trim($child->nodeValue) == '') {
                $body->removeChild($child);
                $i--;
            }
            /** @var \DOMElement $child */

            if ($i == 0) {
                continue;
            }
            if (strtolower($child->nodeName) == 'ul' && strtolower($children->item($i - 1)->nodeName) == 'ul') {
                $appendToPrev = true;
            }
            if (strtolower($child->nodeName) == 'ol' && strtolower($children->item($i - 1)->nodeName) == 'ol') {
                $startPrev = $children->item($i - 1)->getAttribute('start');
                if ($startPrev) {
                    $startPrev = IntVal($startPrev);
                } else {
                    $startPrev = 1;
                }
                $currExpected = $startPrev;
                foreach ($children->item($i - 1)->childNodes as $tmpChild) {
                    if (is_a($tmpChild, \DOMElement::class) && strtolower($tmpChild->nodeName) == 'li') {
                        $currExpected++;
                    }
                }
                $currStart = $child->getAttribute('start');
                if (!$currStart || IntVal($currStart) == $currExpected) {
                    $appendToPrev = true;
                }
            }

            if ($appendToPrev) {
                foreach ($child->childNodes as $subchild) {
                    $child->removeChild($subchild);
                    $children->item($i - 1)->appendChild($subchild);
                }
                $body->removeChild($child);
                $i--;
            }
        }

        return static::renderDomToHtml($body, true);
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
            $ins  = \Yii::t('diff', 'plain_text_ins');
            $text = '[' . $ins . ']' . $matches[1] . '[/' . $ins . ']';
            return $text;
        }, $text);

        $text = preg_replace_callback("/<del[^>]*>(.*)<\/del>/siU", function ($matches) {
            $ins  = \Yii::t('diff', 'plain_text_del');
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
     * @param Amendment $amendment
     * @param string $direction [top, bottom, right, left]
     * @return string
     */
    public static function amendmentDiffTooltip(Amendment $amendment, $direction = '')
    {
        $url = UrlHelper::createAmendmentUrl($amendment, 'ajax-diff');
        return '<button tabindex="0" type="button" data-toggle="popover" ' .
            'class="amendmentAjaxTooltip link" data-initialized="0" ' .
            'data-url="' . Html::encode($url) . '" title="' . \Yii::t('amend', 'ajax_diff_title') . '" ' .
            'data-amendment-id="' . $amendment->id . '" data-placement="' . Html::encode($direction) . '">' .
            '<span class="glyphicon glyphicon-eye-open"></span></button>';
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
     * @param bool $skipBody
     * @return string
     */
    public static function renderDomToHtml(\DOMNode $node, $skipBody = false)
    {
        if (is_a($node, \DOMElement::class)) {
            if ($node->nodeName == 'br') {
                return '<br>';
            }
            /** @var \DOMElement $node */
            $str = '';
            if (!$skipBody || strtolower($node->nodeName) != 'body') {
                $str .= '<' . $node->nodeName;
                foreach ($node->attributes as $key => $val) {
                    $val = $node->getAttribute($key);
                    $str .= ' ' . $key . '="' . Html::encode($val) . '"';
                }
                $str .= '>';
            }
            foreach ($node->childNodes as $child) {
                $str .= static::renderDomToHtml($child);
            }
            if (!$skipBody || strtolower($node->nodeName) != 'body') {
                $str .= '</' . $node->nodeName . '>';
            }
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
            $nodeArr = [
                'name'     => $node->nodeName,
                'classes'  => '',
                'children' => [],
            ];
            foreach ($node->childNodes as $child) {
                $nodeArr['children'][] = static::getDomDebug($child);
            }
            return $nodeArr;
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

    /**
     * @param string $str
     * @return string
     */
    public static function encodeAddShy($str)
    {
        $str       = Html::encode($str);
        $shyAfters = ['itglieder', 'enden', 'voll', 'undex', 'gierten', 'wahl', 'andes'];
        foreach ($shyAfters as $shyAfter) {
            $str = str_replace($shyAfter, $shyAfter . '&shy;', $str);
        }
        return $str;
    }

    /**
     * @param \DOMNode $node
     * @return \DOMNode[]
     */
    private static function stripInsDelMarkersInt(\DOMNode $node)
    {
        if (!is_a($node, \DOMElement::class)) {
            return [$node];
        }

        /** @var \DOMElement $node */
        if ($node->nodeName == 'del') {
            return [];
        }
        if ($node->nodeName == 'ins') {
            $children = [];
            while ($node->childNodes->length > 0) {
                $child = $node->childNodes->item(0);
                $node->removeChild($child);
                $children[] = $child;
            }
            return $children;
        }

        $classes = [];
        if ($node->getAttribute('class')) {
            $classes = explode(' ', $node->getAttribute('class'));
        }
        if (in_array('deleted', $classes)) {
            return [];
        }

        if (in_array('inserted', $classes)) {
            $classes    = array_filter($classes, function ($class) {
                return ($class != 'inserted');
            });
            $newClasses = trim(implode(' ', $classes));
            if ($newClasses != '') {
                $node->setAttribute('class', $newClasses);
            } else {
                $node->removeAttribute('class');
            }
        }

        $children = [];
        while ($node->childNodes->length > 0) {
            $child = $node->childNodes->item(0);
            $node->removeChild($child);
            $modifiedChild = static::stripInsDelMarkersInt($child);
            $children      = array_merge($children, $modifiedChild);
        }
        foreach ($children as $child) {
            $node->appendChild($child);
        }

        return [$node];
    }

    /**
     * @param string $html
     * @return string
     */
    public static function stripInsDelMarkers($html)
    {
        $body         = static::html2DOM($html);
        $strippedBody = static::stripInsDelMarkersInt($body);
        $str          = '';
        foreach ($strippedBody[0]->childNodes as $child) {
            $str .= static::renderDomToHtml($child);
        }
        return $str;
    }
}
