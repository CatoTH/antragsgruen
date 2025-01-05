<?php

namespace app\components;

use app\models\db\Amendment;
use app\models\SectionedParagraph;
use app\models\exceptions\{FormError, Internal};
use yii\helpers\Html;

class HTMLTools
{
    public const KNOWN_BLOCK_ELEMENTS = ['div', 'ul', 'li', 'ol', 'blockquote', 'pre', 'p', 'section',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

    public const KNOWN_OL_CLASSES = ['decimalDot', 'decimalCircle', 'lowerAlpha', 'upperAlpha'];
    public const OL_DECIMAL_DOT = 'decimalDot';
    public const OL_DECIMAL_CIRCLE = 'decimalCircle';
    public const OL_LOWER_ALPHA = 'lowerAlpha';
    public const OL_UPPER_ALPHA = 'upperAlpha';

    public static function isStringCachable(string $str): bool
    {
        return strlen($str) > 1000;
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
        foreach (self::KNOWN_BLOCK_ELEMENTS as $element) {
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
            if ($html === false) {
                throw new FormError('Could not normalize HTML');
            }
        }

        $html = str_replace(chr(194) . chr(160), ' ', $html); // U+00A0 / No-break space, long space
        $html = str_replace(chr(239) . chr(187) . chr(191), '', $html); // U+FEFF / Byte order Mark
        $html = str_replace(chr(226) . chr(128) . chr(139	), '', $html); // U+200B / Zero Width Space

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
        $html = self::cleanMessedUpHtmlCharacters($html);
        $html = str_replace("\r", '', $html);
        // @TODO
        return $html;
    }

    public static function correctHtmlErrors(string $htmlIn, bool $linkify = false): string
    {
        $cacheKey = 'correctHtmlErrors_' . md5($htmlIn);
        if (self::isStringCachable($htmlIn) && \Yii::$app->getCache()->exists($cacheKey)) {
            return \Yii::$app->getCache()->get($cacheKey);
        }

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

        $str = self::purify($configInstance, $htmlIn);

        $str = self::cleanMessedUpHtmlCharacters($str);
        if (self::isStringCachable($htmlIn)) {
            \Yii::$app->getCache()->set($cacheKey, $str);
        }

        return $str;
    }

    /**
     * Used for cleaning up the HTML entered in the translation tool.
     * Fixes HTML problems, removes JavaScript, but allows some placeholders in the HREF of links.
     */
    public static function cleanHtmlTranslationString(string $html): string
    {
        $html = self::correctHtmlErrors($html);

        $html = preg_replace_callback('/href\s*=([\'"]).*\\1/siuU', function (array $matches): string {
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
        $dom = self::html2DOM($html);

        $hasChanged = false;
        /** @var \DOMElement|null $wrapP */
        $wrapP = null;
        for ($i = 0; $i < $dom->childNodes->length; $i++) {
            $childNode = $dom->childNodes->item($i);
            /** @var \DOMNode $childNode */
            $isText   = is_a($childNode, \DOMText::class);
            $isInline = !in_array($childNode->nodeName, self::KNOWN_BLOCK_ELEMENTS);
            if ($isText || $isInline) {
                $hasChanged = true;
                if ($wrapP === null) {
                    /** @var \DOMElement $wrapP */
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
            return self::renderDomToHtml($dom, true);
        } else {
            return $html;
        }
    }

    /**
     * @param string[] $forbiddenFormattings
     */
    public static function cleanSimpleHtml(string $htmlIn, array $forbiddenFormattings = []): string
    {
        $cacheKey = 'cleanSimpleHtml_' . implode(',', $forbiddenFormattings) . '_' . md5($htmlIn);
        /*
        if (self::isStringCachable($htmlIn) && \Yii::$app->getCache()->exists($cacheKey) && false) {
            return \Yii::$app->getCache()->get($cacheKey);
        }
        */

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

        $allowedClasses = array_merge(['underline', 'subscript', 'superscript'], self::KNOWN_OL_CLASSES);

        if (!in_array('strike', $forbiddenFormattings)) {
            $allowedClasses[] = 'strike';
        }

        $allowedAttributes = ['style', 'href', 'class', 'li.value', 'ol.start'];

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
        $html = self::purify($configInstance, $html);

        // Text always needs to be in a block container. This is the normal case anyway,
        // however sometimes CKEditor + Lite Change Tracking produces messed up HTML that we need to fix here
        $html = self::wrapOrphanedTextWithP($html);

        $html = str_ireplace("</li>", "</li>\n", $html);
        $html = str_ireplace("<ul>", "<ul>\n", $html);
        $html = str_ireplace("</ul>", "</ul>\n", $html);
        $html = str_ireplace("</p>", "</p>\n", $html);
        $html = str_ireplace("<br>", "<br>\n", $html);

        $html = preg_replace("/\\n+/siu", "\n", $html);
        $html = str_replace(chr(194) . chr(160), ' ', $html); // Long space
        $html = str_replace('&nbsp;', ' ', $html);
        $html = preg_replace('/(?<tag><(p|ul|ol|li|div|blockquote)( [^>]*)>)<br>\\n/siu', '$1', $html);
        $html = preg_replace('/<br>\\n *(?<tag><\/(p|ul|ol|li|div|blockquote)>)/siu', '$1', $html);
        $html = str_replace("<br>\n ", "<br>\n", $html);

        $html = self::cleanMessedUpHtmlCharacters($html);
        $html = preg_replace('/<p> +/siu', '<p>', $html);
        $html = preg_replace('/ +<\/p>/siu', '</p>', $html);
        $html = preg_replace('/ +<\/li>/siu', '</li>', $html);
        $html = preg_replace('/ +<br>/siu', '<br>', $html);
        $html = str_replace("<p><br>\n", "<p>", $html);
        $html = str_replace("<p></p>", "", $html);

        $html = trim($html);

        if (self::isStringCachable($htmlIn)) {
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
        if ($value !== null && $value !== '') {
            return $value;
        }
        switch ($formatting) {
            case HTMLTools::OL_UPPER_ALPHA:
                $first = (int)floor(($counter - 1) / 26);
                $second = ($counter - 1) - $first * 26;
                if ($first > 0) {
                    return chr(ord('A') + $first - 1) . chr(ord('A') + $second);
                } else {
                    return chr(ord('A') + $second);
                }
            case HTMLTools::OL_LOWER_ALPHA:
                $first = (int)floor(($counter - 1) / 26);
                $second = ($counter - 1) - $first * 26;
                if ($first > 0) {
                    return chr(ord('a') + $first - 1) . chr(ord('a') + $second);
                } else {
                    return chr(ord('a') + $second);
                }
            case HTMLTools::OL_DECIMAL_DOT:
            case HTMLTools::OL_DECIMAL_CIRCLE:
            default:
                return (string)$counter;
        }
    }

    public static function getLiValueFormatted(int $counter, ?string $value, string $formatting): string
    {
        $value = self::getLiValue($counter, $value, $formatting);
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
            if ($start > 0) {
                $liCount = intval($start) - 1;
            }
            $formatting = self::OL_DECIMAL_DOT;
            if ($element->hasAttribute('class')) {
                $classes = explode(' ', $element->getAttribute('class'));
                foreach ($classes as $class) {
                    if ($element->nodeName === 'ol' && in_array($class, self::KNOWN_OL_CLASSES)) {
                        $formatting = $class;
                    }
                }
            }

            foreach ($children as $child) {
                if (!is_a($child, \DOMElement::class)) {
                    continue;
                }
                /** @var \DOMElement $child */
                $liCount = self::getNextLiCounter($child, $liCount);
                self::explicitlySetLiValuesInt($child, $liCount, $formatting);
            }
            return;
        }

        if ($element->nodeName === 'li') {
            $formatting = $formatting ?? self::OL_DECIMAL_DOT;
            if (!$element->hasAttribute('value')) {
                $liVal = self::getLiValue($counter, null, $formatting);
                $element->setAttribute('value', $liVal);
            }
        }

        foreach ($children as $child) {
            if (!is_a($child, \DOMElement::class)) {
                continue;
            }
            /** @var \DOMElement $child */
            self::explicitlySetLiValuesInt($child);
        }
    }

    public static function explicitlySetLiValues(string $html): string
    {
        $dom = self::html2DOM($html);
        self::explicitlySetLiValuesInt($dom);

        return self::renderDomToHtml($dom, true);
    }

    /**
     * @return SectionedParagraph[]
     * @throws Internal
     */
    private static function sectionSimpleHTMLInt(\DOMElement $element, int &$paragraphNoWithoutSplit, bool $split, bool $splitListItems, string $pre, string $post): array
    {
        $origParagraphNoWithoutSplit = $paragraphNoWithoutSplit;

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

        $lino = 0;
        if ($element->nodeName === 'ol') {
            $start = $element->getAttribute('start');
            if ($start > 0) {
                $lino = intval($start) - 1;
            }
        }

        for ($i = 0; $i < $children->length; $i++) {
            $child = $children->item($i);
            switch (get_class($child)) {
                case 'DOMElement':
                    /** @var \DOMElement $child */
                    if ($child->nodeName === 'br') {
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
                        $newArrs = self::sectionSimpleHTMLInt($child, $paragraphNoWithoutSplit, $split, $splitListItems, $newPre, $newPost);
                        if ($pendingInline === null) {
                            $pendingInline = '';
                        }
                        foreach ($newArrs as $str) {
                            $pendingInline .= $str->html;
                        }
                    } else {
                        if ($pendingInline !== null) {
                            $return[] = new SectionedParagraph($pre . $pendingInline . $post, $origParagraphNoWithoutSplit);
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
                            $newArrs = self::sectionSimpleHTMLInt($child, $paragraphNoWithoutSplit, $split, $splitListItems, $newPre, $newPost);
                            if (count($return) > 0) {
                                foreach ($newArrs as $arr) {
                                    $arr->paragraphWithoutLineSplit++;
                                }
                                $paragraphNoWithoutSplit++;
                            }
                            $return  = array_merge($return, $newArrs);
                        } elseif ($child->nodeName === 'li') {
                            $lino = self::getNextLiCounter($child, $lino);
                            $value = $child->getAttribute('value');
                            $newPre  = str_replace('#LINO#', (string)$lino, $pre);
                            if ($value) {
                                $newPre .= '<' . $child->nodeName . ' value="' . $value . '">';
                            } else {
                                $newPre .= '<' . $child->nodeName . '>';
                            }
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = self::sectionSimpleHTMLInt($child, $paragraphNoWithoutSplit, $split, $splitListItems, $newPre, $newPost);
                            $return  = array_merge($return, $newArrs);
                        } elseif (in_array($child->nodeName, ['ul', 'blockquote', 'p', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
                            $newPre  = $pre . '<' . $child->nodeName . '>';
                            $newPost = '</' . $child->nodeName . '>' . $post;
                            $newArrs = self::sectionSimpleHTMLInt($child, $paragraphNoWithoutSplit, $split, $splitListItems, $newPre, $newPost);
                            if (count($return) > 0) {
                                foreach ($newArrs as $arr) {
                                    $arr->paragraphWithoutLineSplit++;
                                }
                                $paragraphNoWithoutSplit++;
                            }
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
            $return[] = new SectionedParagraph($pre . $pendingInline . $post, $origParagraphNoWithoutSplit);
        }
        return $return;
    }

    public static function html2DOM(string $html, bool $correctBefore = true): \DOMElement
    {
        if ($correctBefore) {
            $html = self::correctHtmlErrors($html);
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
     * @return SectionedParagraph[]
     * @throws Internal
     */
    public static function sectionSimpleHTML(string $html, bool $splitListItems = true): array
    {
        $cache = HashedStaticCache::getInstance('sectionSimpleHTML2', [$html, $splitListItems]);

        return $cache->getCached(function () use ($html, $splitListItems) {
            $paragraphNoWithoutSplit = 0;
            $body = self::html2DOM($html);
            $result = self::sectionSimpleHTMLInt($body, $paragraphNoWithoutSplit, true, $splitListItems, '', '');
            if ($splitListItems) {
                for ($i = 0; $i < count($result); $i++) {
                    $result[$i]->paragraphWithLineSplit = $i;
                }
            }
            return $result;
        });
    }

    /*
     * Tries to restore the original HTML after re-combining reviously split markup.
     * Currently, this only joins adjacent top-level lists.
     */
    public static function removeSectioningFragments(string $html): string
    {
        $body     = self::html2DOM($html);
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
                /** @var \DOMElement $olElement */
                $olElement = $children->item($i - 1);
                $startPrev = $olElement->getAttribute('start');
                if ($startPrev) {
                    $startPrev = IntVal($startPrev);
                } else {
                    $startPrev = 1;
                }
                $currExpected = $startPrev;
                foreach ($olElement->childNodes as $tmpChild) {
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

        return self::renderDomToHtml($body, true);
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

    private static bool $LINKS;
    /** @var string[] */
    private static array $LINK_CACHE;

    public static function toPlainText(string $html, bool $linksAtEnd = false): string
    {
        $html = str_replace("\n", "", $html);
        $html = str_replace("\r", "", $html);
        $html = str_replace(" />", ">", $html);

        self::$LINKS      = $linksAtEnd;
        self::$LINK_CACHE = [];

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

            if (self::$LINKS) {
                $newnr                      = count(self::$LINK_CACHE) + 1;
                self::$LINK_CACHE[$newnr] = $matches[1];
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

        if ($linksAtEnd && count(self::$LINK_CACHE) > 0) {
            $text .= "\n\n\nLinks:\n";
            foreach (self::$LINK_CACHE as $nr => $link) {
                $text .= "[$nr] $link\n";
            }
        }

        return trim($text);
    }

    public static function getTooltipIcon(string $tooltip, string $placement = 'top', bool $html = false): string
    {
        $html = ($html ? 'data-html="true" ' : '');
        return '<span class="tooltipIcon glyphicon glyphicon-info-sign" data-toggle="tooltip" data-placement="' . $placement . '" ' .
               $html .
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
                $str .= self::renderDomToHtml($child);
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
            $nodeArr = [
                'name'     => $node->nodeName,
                'classes'  => '',
                'children' => [],
            ];
            foreach ($node->childNodes as $child) {
                $nodeArr['children'][] = self::getDomDebug($child);
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

        $targetHtml = (UrlHelper::getCurrentConsultation()?->getSettings()->externalLinksNewWindow ? ' target="_blank"' : '');

        $urlsearch[]  = "/([({\\[\\|>\\s])((https?|ftp|news):\\/\\/|mailto:)($hostUrlPattern)/siu";
        $urlreplace[] = "\\1<a rel=\"nofollow\" href=\"\\2\\4\"$targetHtml>\\2\\4</a>";

        $urlsearch[]  = "/^((https?|ftp|news):\\/\\/|mailto:)($hostUrlPattern)/siu";
        $urlreplace[] = "<a rel=\"nofollow\" href=\"\\1\\3\"$targetHtml>\\1\\3</a>";

        $wwwsearch[]  = "/([({\\[\\|>\\s])((?<![\\/\\/])www\\.)($hostUrlPattern)/siu";
        $wwwreplace[] = "\\1<a rel=\"nofollow\" href=\"http://\\2\\3\"$targetHtml>\\2\\3</a>";

        $wwwsearch[]  = "/^((?<![\\/\\/])www\\.)($hostUrlPattern)/siu";
        $wwwreplace[] = "<a rel=\"nofollow\" href=\"http://\\1\\2\"$targetHtml>\\1\\2</a>";

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
    private static function stripInsDelMarkersInt(\DOMNode $node): array
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
            $modifiedChild = self::stripInsDelMarkersInt($child);
            $children      = array_merge($children, $modifiedChild);
        }
        foreach ($children as $child) {
            $node->appendChild($child);
        }

        return [$node];
    }

    public static function stripInsDelMarkers(string $html): string
    {
        $body         = self::html2DOM($html);
        $strippedBody = self::stripInsDelMarkersInt($body);
        $str          = '';
        foreach ($strippedBody[0]->childNodes as $child) {
            $str .= self::renderDomToHtml($child);
        }
        return $str;
    }

    /*
     * Hint: It's not 100% guaranteed that $maxLength is not exceeded, as ending HTML tags might be added on the fly.
     */
    public static function trimHtml(string $html, int $maxLength): string
    {
        if (grapheme_strlen($html) <= $maxLength) {
            return $html;
        }

        $safetyGap = 40; // 5-10 ending tags
        $shortenedHtml = mb_substr($html, 0, $maxLength - $safetyGap);
        if (preg_match('/\\w$/', $shortenedHtml)) {
            $shortenedHtml .= '…';
        }

        return self::correctHtmlErrors($shortenedHtml, false);
    }

    public static function createExternalLink(string $htmlContent, string $url, array $additionalAtts = []): string
    {
        $consultation = UrlHelper::getCurrentConsultation();
        if ($consultation?->getSettings()->externalLinksNewWindow) {
            $additionalAtts['target'] = '_blank';
        }

        return Html::a($htmlContent, $url, $additionalAtts);
    }
}
