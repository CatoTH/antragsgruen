<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\exceptions\Internal;
use yii\helpers\Html;

class HTMLTools
{
    public static $KNOWN_BLOCK_ELEMENTS = ['div', 'ul', 'li', 'ol', 'blockquote', 'pre', 'p', 'section',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    public static $KNOWN_OL_CLASSES = ['decimalDot', 'decimalCircle', 'lowerAlpha', 'upperAlpha'];
    const OL_DECIMAL_DOT = 'decimalDot';
    const OL_DECIMAL_CIRCLE = 'decimalCircle';
    const OL_LOWER_ALPHA = 'lowerAlpha';
    const OL_UPPER_ALPHA = 'upperAlpha';

    public static function isStringCachable(string $str): bool
    {
        return strlen($str) > 1000;
    }

    /*
     * Required by HTML Purifier to handle Umlaut domains
     */
    public static function loadNetIdna2(): void
    {
        $dir  = __DIR__ . DIRECTORY_SEPARATOR . 'Net_IDNA2-0.1.1' . DIRECTORY_SEPARATOR . 'Net' . DIRECTORY_SEPARATOR;
        $dir2 = $dir . 'IDNA2' . DIRECTORY_SEPARATOR;
        @require_once $dir2 . 'Exception.php';
        @require_once $dir2 . 'Exception' . DIRECTORY_SEPARATOR . 'Nameprep.php';
        @require_once $dir . 'IDNA2.php';
    }

    public static function purify(\HTMLPurifier_Config $config, string $html): string {
        /** @var \HTMLPurifier_HTMLDefinition $def */
        $def = $config->getHTMLDefinition(true);

        // Overwriting standard LI implementation, allowing non-integer values
        $li = $def->addBlankElement('li');
        $li->attr['value'] = new \HTMLPurifier_AttrDef_Text();
        $li->attr['type'] = 'Enum#s:1,i,I,a,A,disc,square,circle';

        $def->addAttribute('ins', 'aria-label', 'Text');
        $def->addAttribute('del', 'aria-label', 'Text');
        $def->addAttribute('span', 'aria-label', 'Text');
        foreach (static::$KNOWN_BLOCK_ELEMENTS as $element) {
            $def->addAttribute($element, 'aria-label', 'Text');
        }

        $purifier = new \HTMLPurifier($config);
        $purifier->config->set('Cache.SerializerPath', \Yii::$app->getRuntimePath());
        $purifier->config->set('Cache.SerializerPermissions', 0775);

        return $purifier->purify($html);
    }

    public static function cleanMessedUpHtmlCharacters(string $html): string
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

    public static function cleanTrustedHtml(string $html): string
    {
        $html = static::cleanMessedUpHtmlCharacters($html);
        $html = str_replace("\r", '', $html);
        // @TODO
        return $html;
    }

    public static function correctHtmlErrors(string $htmlIn, bool $linkify = false): string
    {
        $cacheKey = 'correctHtmlErrors_' . md5($htmlIn);
        if (static::isStringCachable($htmlIn) && \Yii::$app->getCache()->exists($cacheKey)) {
            return \Yii::$app->getCache()->get($cacheKey);
        }

        static::loadNetIdna2();
        $configInstance = \HTMLPurifier_Config::create([
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
        ]);
        $configInstance->autoFinalize = false;

        $def                                                    = $configInstance->getHTMLDefinition(true);
        $def->info_global_attr['data-moving-partner-id']        = new \HTMLPurifier_AttrDef_Text();
        $def->info_global_attr['data-moving-partner-paragraph'] = new \HTMLPurifier_AttrDef_Text();

        $str = static::purify($configInstance, $htmlIn);

        $str = static::cleanMessedUpHtmlCharacters($str);
        if (static::isStringCachable($htmlIn)) {
            \Yii::$app->getCache()->set($cacheKey, $str);
        }

        return $str;
    }

    /**
     * Used for cleaning up the HTML entered in the translation tool.
     * Fixes HTML problems, removes JavaScript, but allows some placeholders in the HREF of links.
     *
     * @param string $html
     * @return string
     */
    public static function cleanHtmlTranslationString($html)
    {
        $html = static::correctHtmlErrors($html);

        $html = preg_replace_callback('/href\s*=([\'"]).*\\1/siuU', function ($matches) {
            $part = $matches[0];
            $part = str_replace('%25URL%25', '%URL%', $part);
            $part = str_replace('%25HOME%25', '%HOME%', $part);
            $part = str_replace('%25SITE_URL%25', '%SITE_URL%', $part);
            return $part;
        }, $html);

        return $html;
    }

    public static function wrapOrphanedTextWithP(string $html): string
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
        if (static::isStringCachable($htmlIn) && \Yii::$app->getCache()->exists($cacheKey) && false) {
            return \Yii::$app->getCache()->get($cacheKey);
        }

        $html = str_replace("\r", '', $htmlIn);

        // When coming from amendment creating
        // should only happen in some edge cases where the editor was not used correctly
        $html = preg_replace('/<del[^>]*>.*<\/del>/siuU', '', $html);

        // Remove <a>...</a>
        $html = preg_replace('/<a>(.*)<\/a>/siuU', '$1', $html);

        // When editing amendments, list items are split into <ol start="2" class="upperAlpha"> items.
        // After editing, it should be merged into one list again.
        $html = preg_replace('/<\/ol>\s*<ol( [^>]*)?>/siu', '', $html);
        $html = preg_replace('/<\/ol>\s*<\/div>\s*<div[^>]*>\s*<ol( [^>]*)?>/siu', '', $html);

        $allowedTags = [
            'p', 'strong', 'em', 'ul', 'ol', 'li', 'span', 'a', 'br', 'blockquote',
            'sub', 'sup', 'pre', 'h1', 'h2', 'h3', 'h4'
        ];

        $allowedClasses = array_merge(['underline', 'subscript', 'superscript'], static::$KNOWN_OL_CLASSES);

        if (!in_array('strike', $forbiddenFormattings)) {
            $allowedClasses[] = 'strike';
        }

        $allowedAttributes = ['style', 'href', 'class', 'li.value', 'ol.start'];

        $html = str_replace('<p></p>', '<p>###EMPTY###</p>', $html);

        static::loadNetIdna2();
        $configInstance = \HTMLPurifier_Config::create([
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
        ]);
        $configInstance->autoFinalize = false;
        $html = static::purify($configInstance, $html);

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

    public static function stripEmptyBlockParagraphs(string $html): string
    {
        do {
            $htmlPre = $html;
            $html    = preg_replace('/<(p|div|li|ul|ol|h1|h2|h3|h4|h5)>\s*<\/\1>/siu', '', $html);
        } while ($htmlPre != $html);

        $html = preg_replace("/\\n\s*\\n+/siu", "\n", $html);
        $html = trim($html);

        return $html;
    }

    public static function prepareHTMLForCkeditor(string $html): string
    {
        // When editing amendments, list items are split into <ol start="2"> items
        // (it's possible to edit only one list item)
        // However, CKEDITOR strips the start.
        $html = preg_replace('/<\/ol>\s*<ol( start=\"?\'?\d*\"?\'?)?\">/siu', '', $html);

        $html = preg_replace('/(<[^\/][^>]*>) (\w)/siu', '\\1&nbsp;\\2', $html);
        $html = preg_replace('/(\w) (<\/[^>]*>)/siu', '\\1&nbsp;\\2', $html);
        return $html;
    }

    public static function getNextLiCounter(\DOMElement $li, int $oldLiNo): int
    {
        $liNo = $oldLiNo + 1;
        $value = $li->getAttribute('value');
        if ($value) {
            if (is_numeric($value)) {
                $liNo = intval($value);
            }
            if (strlen($value) === 1) {
                $ord = ord(strtolower($value));
                if ($ord >= ord('a') && $ord <= ord('z')) {
                    $liNo = $ord - ord('a') + 1;
                }
            }
        }
        return $liNo;
    }

    public static function getLiValue(int $counter, ?string $value, string $formatting): string
    {
        switch ($formatting) {
            case HTMLTools::OL_DECIMAL_CIRCLE:
                return ($value !== null && $value !== '' ? $value : $counter);
            case HTMLTools::OL_UPPER_ALPHA:
                return ($value !== null && $value !== '' ? $value : chr(ord('A') + $counter - 1));
            case HTMLTools::OL_LOWER_ALPHA:
                return ($value !== null && $value !== '' ? $value : chr(ord('a') + $counter - 1));
            case HTMLTools::OL_DECIMAL_DOT:
            default:
                return ($value !== null && $value !== '' ? $value : $counter);
        }
    }

    public static function getLiValueFormatted(int $counter, ?string $value, string $formatting): string
    {
        $value = static::getLiValue($counter, $value, $formatting);
        switch ($formatting) {
            case HTMLTools::OL_DECIMAL_CIRCLE:
                return '(' . $value . ')';
            case HTMLTools::OL_UPPER_ALPHA:
            case HTMLTools::OL_LOWER_ALPHA:
            case HTMLTools::OL_DECIMAL_DOT:
            default:
                return $value . '.';
        }
    }

    private static function explicitlySetLiValuesInt(\DOMElement $element, ?int $counter = null, ?string $formatting = null): void
    {
        $children      = $element->childNodes;

        if ($element->nodeName === 'ol' || $element->nodeName === 'ul') {
            $liCount          = 0;
            $start = $element->getAttribute('start');
            if ($start !== null && $start > 0) {
                $liCount = intval($start) - 1;
            }
            $formatting = static::OL_DECIMAL_DOT;
            if ($element->hasAttribute('class')) {
                $classes = explode(' ', $element->getAttribute('class'));
                foreach ($classes as $class) {
                    if ($element->nodeName === 'ol' && in_array($class, static::$KNOWN_OL_CLASSES)) {
                        $formatting = $class;
                    }
                }
            }

            foreach ($children as $child) {
                if (!is_a($child, \DOMElement::class)) {
                    continue;
                }
                /** @var \DOMElement $child */
                $liCount = static::getNextLiCounter($child, $liCount);
                static::explicitlySetLiValuesInt($child, $liCount, $formatting);
            }
            return;
        }

        if ($element->nodeName === 'li') {
            $formatting = $formatting ?? static::OL_DECIMAL_DOT;
            if (!$element->hasAttribute('value')) {
                $liVal = static::getLiValue($counter, null, $formatting);
                $element->setAttribute('value', $liVal);
            }
        }

        foreach ($children as $child) {
            if (!is_a($child, \DOMElement::class)) {
                continue;
            }
            /** @var \DOMElement $child */
            static::explicitlySetLiValuesInt($child);
        }
    }

    public static function explicitlySetLiValues(string $html): string
    {
        $dom = static::html2DOM($html);
        static::explicitlySetLiValuesInt($dom);

        return static::renderDomToHtml($dom, true);
    }

    /**
     * @param \DOMElement $element
     * @param bool $split
     * @param bool $splitListItems
     * @param string $pre
     * @param string $post
     * @return string[]
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
        if ($element->nodeName === 'ol') {
            $start = $element->getAttribute('start');
            if ($start !== null && $start > 0) {
                $lino = intval($start) - 1;
            }
        }

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
                        $attributes = [];
                        switch ($child->nodeName) {
                            case 'a':
                                if ($child->hasAttribute('class')) {
                                    $attributes['class'] = $child->getAttribute('class');
                                }
                                $attributes['href'] = ($child->hasAttribute('href') ? $child->getAttribute('href') : '');
                                break;
                            case 'span':
                                if ($child->hasAttribute('class')) {
                                    $attributes['class'] = $child->getAttribute('class');
                                }
                                break;
                            case 'ul':
                            case 'ol':
                                if ($child->hasAttribute('class')) {
                                    $attributes['class'] = $child->getAttribute('class');
                                }
                                if ($child->hasAttribute('start')) {
                                    $attributes['start'] = $child->getAttribute('start');
                                }
                                break;
                            case 'li':
                                if ($child->hasAttribute('value')) {
                                    $attributes['value'] = $child->getAttribute('value');
                                }
                                break;
                        }
                        $newPre = '<' . $child->nodeName;
                        foreach ($attributes as $key => $val) {
                            $newPre .= ' ' . $key . '="' . Html::encode($val) . '"';
                        }
                        $newPre .= '>';
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

                        /*
                         * Hints about numbering of ordered lists:
                         * - Each OL gets a "start" attribute, with the numeric value
                         * - LIs are receiving a "value" if it explicitly set by the user. this value overrides the natural order
                         * - Unlike in standard HTML, non-numeric values are allowed as LI[value]
                         * - If a strictly numeric number (1-100) is encountered as LI[value], this affects the counter of the OL,
                         *   both of the current OL parent item and of the parent OL of each subsequent LI
                         * - The same happens with a single-letter character; A is treated equally as 1, B as 2, and so on
                         * - If a non-strictly-numbered LI[value] is encontered (like "3b"), the counter of the OL is unaffected
                         */
                        if ($child->nodeName === 'ol') {
                            $classes = $child->getAttribute('class');
                            $newPre = $pre . '<' . $child->nodeName;
                            if ($classes) {
                                $newPre .= ' class="' . Html::encode($classes) . '"';
                            }
                            $newPre .= ' start="#LINO#">';
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = static::sectionSimpleHTMLInt($child, $split, $splitListItems, $newPre, $newPost);
                            $return  = array_merge($return, $newArrs);
                        } elseif ($child->nodeName === 'li') {
                            $lino = static::getNextLiCounter($child, $lino);
                            $value = $child->getAttribute('value');
                            $newPre  = str_replace('#LINO#', $lino, $pre);
                            if ($value) {
                                $newPre .= '<' . $child->nodeName . ' value="' . $value . '">';
                            } else {
                                $newPre .= '<' . $child->nodeName . '>';
                            }
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = static::sectionSimpleHTMLInt($child, $split, $splitListItems, $newPre, $newPost);
                            $return  = array_merge($return, $newArrs);
                        } elseif (in_array($child->nodeName, ['ul', 'blockquote', 'p', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
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

    public static function html2DOM(string $html, bool $correctBefore = true): \DOMElement
    {
        if ($correctBefore) {
            $html = static::correctHtmlErrors($html);
        }

        $src_doc = new \DOMDocument();
        $src_doc->loadHTML('<html><head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head><body>' . $html . "</body></html>");
        $bodies = $src_doc->getElementsByTagName('body');
        /** @var \DOMElement $dom */
        $dom    = $bodies->item(0);

        return $dom;
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
     * @return string[]
     * @throws Internal
     */
    public static function sectionSimpleHTML($html, $splitListItems = true)
    {
        $cacheFunc = 'sectionSimpleHTML';
        $cacheDeps = [$html, $splitListItems];

        $cache = HashedStaticCache::getCache($cacheFunc, $cacheDeps);
        if ($cache !== false) {
            return $cache;
        }

        $body = static::html2DOM($html);
        $result = static::sectionSimpleHTMLInt($body, true, $splitListItems, '', '');

        HashedStaticCache::setCache($cacheFunc, $cacheDeps, $result);

        return $result;
    }

    /*
     * Tries to restore the original HTML after re-combining reviously split markup.
     * Currently, this only joins adjacent top-level lists.
     */
    public static function removeSectioningFragments(string $html): string
    {
        $body     = static::html2DOM($html);
        $children = $body->childNodes;
        for ($i = 0; $i < $children->length; $i++) {
            $appendToPrev = false;
            $child        = $children->item($i);
            if (is_a($child, \DOMText::class) && trim($child->nodeValue) === '') {
                $body->removeChild($child);
                $i--;
                continue;
            }
            /** @var \DOMElement $child */

            if ($i === 0) {
                continue;
            }
            if (strtolower($child->nodeName) === 'ul' && strtolower($children->item($i - 1)->nodeName) === 'ul') {
                $appendToPrev = true;
            }
            if (strtolower($child->nodeName) === 'ol' && strtolower($children->item($i - 1)->nodeName) === 'ol') {
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
                while ($child->childNodes->length) {
                    $subchild = $child->childNodes->item(0);
                    $child->removeChild($subchild);
                    $children->item($i - 1)->appendChild($subchild);
                }
                $body->removeChild($child);
                $i--;
            }
        }

        return static::renderDomToHtml($body, true);
    }

    public static function plainToHtml(string $text, bool $insertLinks = true): string
    {
        $html = nl2br(htmlentities($text, ENT_COMPAT, 'UTF-8'));
        if ($insertLinks) {
            $url_maxlen       = 250;
            $url_maxlen_end   = 50;
            $url_maxlen_host  = 150;
            $url_patter_host  = "[-a-zäöüß0-9\_\.]";
            $url_pattern      = "([-a-zäöüß0-9\_\$\.\:;\/?=\+\~@,%#!\'\[\]\|]|\&(?!amp\;|lt\;|gt\;|quot\;)|\&amp\;)";
            $url_pattern_ende = "([-a-zäöüß0-9\_\$\:\/=\+\~@%#\|]|\&(?!amp\;|lt\;|gt\;|quot\;)|\&amp\;)";

            $end_pattern      = "($url_pattern_ende|($url_pattern*\($url_pattern{0,$url_maxlen_end}\)){1,3})";
            $host_url_pattern = "$url_patter_host{1,$url_maxlen_host}(\/?($url_pattern{0,$url_maxlen}$end_pattern)?)?";

            $urlsearch[]  = "/([({\[\|>\s])((https?):\/\/|mailto:)($host_url_pattern)/siu";
            $urlreplace[] = "\\1<a rel=\"nofollow\" target=\"_blank\" href=\"\\2\\4\">\\2\\4</a>";

            $urlsearch[]  = "/^((https?):\/\/|mailto:)($host_url_pattern)/siu";
            $urlreplace[] = "<a rel=\"nofollow\" target=\"_blank\" href=\"\\1\\3\">\\1\\3</a>";

            $wwwsearch[]  = "/([({\[\|>\s])((?<![\/\/])www\.)($host_url_pattern)/siu";
            $wwwreplace[] = "\\1<a rel=\"nofollow\" target=\"_blank\" href=\"https://\\2\\3\">\\2\\3</a>";

            $wwwsearch[]  = "/^((?<![\/\/])www\.)($host_url_pattern)/siu";
            $wwwreplace[] = "<a rel=\"nofollow\" target=\"_blank\" href=\"https://\\1\\2\">\\1\\2</a>"; #

            $emailsearch[]  = "/([\s])([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))/si";
            $emailreplace[] = "\\1<a href=\"mailto:\\2\">\\2</a>";
            $emailsearch[]  = "/^([_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,}))/si";
            $emailreplace[] = "<a href=\"mailto:\\0\">\\0</a>";

            $html = preg_replace($wwwsearch, $wwwreplace, $html);
            $html = preg_replace($urlsearch, $urlreplace, $html);
            $html = preg_replace($emailsearch, $emailreplace, $html);
        }

        return $html;
    }


    private static $LINKS;
    private static $LINK_CACHE;

    public static function toPlainText(string $html, bool $linksAtEnd = false): string
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
            return "/" . $matches[1] . "/";
        }, $text);

        $text = preg_replace_callback("/<ins[^>]*>(.*)<\/ins>/siU", function ($matches) {
            $ins  = \Yii::t('diff', 'plain_text_ins');

            return '[' . $ins . ']' . $matches[1] . '[/' . $ins . ']';
        }, $text);

        $text = preg_replace_callback("/<del[^>]*>(.*)<\/del>/siU", function ($matches) {
            $ins  = \Yii::t('diff', 'plain_text_del');

            return '[' . $ins . ']' . $matches[1] . '[/' . $ins . ']';
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

    public static function getTooltipIcon(string $tooltip): string
    {
        return '<span class="glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="top" ' .
                  'aria-label="' . Html::encode(\Yii::t('base', 'aria_tooltip') . ': ' . $tooltip) . '" ' .
                  'data-original-title="' . Html::encode($tooltip) . '"></span>';
    }

    public static function labeledCheckbox(string $formName, string $htmlLabel, bool $checked, ?string $id = null, ?string $tooltip = null): string
    {
        $str = '<label class="labeledCheckbox">';
        $str .= '<input type="checkbox" name="' . Html::encode($formName) . '"';
        if ($checked) {
            $str .= ' checked';
        }
        if ($id) {
            $str .= ' id="' . Html::encode($id) . '"';
        }
        $str .= '>';
        $str .= '<span>' . $htmlLabel . '</span>';
        if ($tooltip) {
            $str .= HTMLTools::getTooltipIcon($tooltip);
        }
        $str .= '</label>';

        return $str;
    }

    /**
     * @param string $formName
     * @param array $options
     * @param string $selected
     * @param array $attributes
     * @param bool $fullSize
     * @param string|null $btnSize [lg, sm, xs]
     * @return string
     */
    public static function fueluxSelectbox($formName, $options, $selected = '', $attributes = [], $fullSize = false, $btnSize = null)
    {
        $btnSize = ($btnSize ? ' btn-' . $btnSize : '');

        $classes = 'btn-group selectlist';
        if ($fullSize) {
            $classes .= ' full-size';
        }
        if (isset($attributes['class'])) {
            $classes .= ' ' . $attributes['class'];
            unset($attributes['class']);
        }
        $str = '<div class="' . $classes . '" data-resize="auto" data-initialize="selectlist"';
        foreach ($attributes as $attrName => $attrVal) {
            $str .= ' ' . $attrName . '="' . Html::encode($attrVal) . '"';
        }
        $str .= '>
  <button class="btn btn-default ' . $btnSize . ' dropdown-toggle" data-toggle="dropdown" type="button" aria-haspopup="true">
    <span class="selected-label"></span>
    <span class="caret"></span>
    <span class="sr-only">' . \Yii::t('base', 'toggle_dropdown') . '</span>
  </button>
  <ul class="dropdown-menu">';
        foreach ($options as $value => $name) {
            if (is_array($name)) {
                $str .= '<li data-value="' . Html::encode($value) . '"';
                if ($value == $selected) {
                    $str .= ' data-selected="true" aria-checked="true"';
                } else {
                    $str .= ' aria-checked="false"';
                }
                if (isset($name['attributes'])) {
                    foreach ($name['attributes'] as $attrName => $attrVal) {
                        $str .= ' ' . $attrName . '="' . Html::encode($attrVal) . '"';
                    }
                }
                $str .= '><a href="#">' . Html::encode($name['title']) . '</a></li>';
            } else {
                $str .= '<li data-value="' . Html::encode($value) . '" ';
                if ($value == $selected) {
                    $str .= ' data-selected="true"';
                }
                $str .= '><a href="#">' . Html::encode($name) . '</a></li>';
            }
        }
        $str .= '</ul>
  <input class="hidden hidden-field" name="' . $formName . '" type="hidden" value="' . Html::encode($selected) . '">
</div>';
        return $str;
    }

    public static function amendmentDiffTooltip(Amendment $amendment, string $direction = '', string $tooltipExtraClass = ''): string
    {
        // $direction values: [top, bottom, right, left]
        $url = UrlHelper::createAmendmentUrl($amendment, 'ajax-diff');
        return '<button tabindex="0" type="button" data-toggle="popover" ' .
            'class="amendmentAjaxTooltip link" data-initialized="0" ' .
            'data-tooltip-extra-class="' . Html::encode($tooltipExtraClass) . '" ' .
            'data-url="' . Html::encode($url) . '" title="' . \Yii::t('amend', 'ajax_diff_title') . '" ' .
            'data-amendment-id="' . $amendment->id . '" data-placement="' . Html::encode($direction) . '">' .
            '<span class="glyphicon glyphicon-eye-open"></span></button>';
    }

    public static function smallTextarea(string $formName, array $options, string $value = ''): string
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

    public static function renderDomToHtml(\DOMNode $node, bool $skipBody = false): string
    {
        if (is_a($node, \DOMElement::class)) {
            if ($node->nodeName === 'br') {
                return '<br>';
            }
            /** @var \DOMElement $node */
            $str = '';
            if (!$skipBody || strtolower($node->nodeName) !== 'body') {
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
            if (!$skipBody || strtolower($node->nodeName) !== 'body') {
                $str .= '</' . $node->nodeName . '>';
            }
            return $str;
        } else {
            /** @var \DOMText $node */
            return Html::encode($node->data);
        }
    }

    public static function getDomDebug(\DOMNode $node): array
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

    public static function textToHtmlWithLink(string $text): string
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

    public static function encodeAddShy(string $str): string
    {
        $str       = Html::encode($str);
        $shyAfters = ['itglieder', 'enden', 'voll', 'undex', 'gierten', 'wahl', 'andes'];
        foreach ($shyAfters as $shyAfter) {
            $str = str_replace($shyAfter, $shyAfter . '&shy;', $str);
        }
        $str = str_replace('&amp;shy;', '&shy;', $str);
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

    public static function stripInsDelMarkers(string $html): string
    {
        $body         = static::html2DOM($html);
        $strippedBody = static::stripInsDelMarkersInt($body);
        $str          = '';
        foreach ($strippedBody[0]->childNodes as $child) {
            $str .= static::renderDomToHtml($child);
        }
        return $str;
    }

    /*
     * Hint: It's not 100% guaranteed that $maxLength is not exceeded, as ending HTML tags might be added on the fly.
     */
    public static function trimHtml(string $html, int $maxLength)
    {
        if (mb_strlen($html) <= $maxLength) {
            return $html;
        }

        $safetyGap = 40; // 5-10 ending tags
        $shortenedHtml = mb_substr($html, 0, $maxLength - $safetyGap);
        if (preg_match('/\\w$/', $shortenedHtml)) {
            $shortenedHtml .= '…';
        }

        $shortenedHtml = static::correctHtmlErrors($shortenedHtml, false);

        return $shortenedHtml;
    }
}
