<?php

namespace app\components;

use yii\helpers\Html;

class LineSplitter
{
    private int $lineLength;
    private string $text;

    public function __construct(string $text, int $lineLength)
    {
        $this->text       = str_replace("\r", "", $text);
        $this->lineLength = $lineLength;
    }


    /**
     * Splits the text into graphemes once up front: calling grapheme_substr / grapheme_strlen on the whole text
     * for every character would make splitLines quadratic in the length of the paragraph.
     *
     * Hint: this deliberately uses ICU (intl) and not preg_split('/\X/u'): PCRE2 brings its own Unicode tables
     * and grapheme rules, which differ between library versions (e.g. for emojis with skin tone modifiers).
     *
     * @return string[]
     */
    private static function splitIntoGraphemes(string $text): array
    {
        $graphemes = [];
        $offset    = 0;
        $length    = strlen($text);
        while ($offset < $length) {
            $grapheme = grapheme_extract($text, 1, GRAPHEME_EXTR_COUNT, $offset, $next);
            if ($grapheme === false || $next <= $offset) {
                // Invalid UTF-8; proceed byte by byte
                $graphemes[] = $text[$offset];
                $offset++;
            } else {
                $graphemes[] = $grapheme;
                $offset      = $next;
            }
        }
        return $graphemes;
    }

    /**
     * Forced line breaks are marked by a trailing ###FORCELINEBREAK###
     *
     * @static
     * @return string[]
     */
    public function splitLines(): array
    {
        $chars    = self::splitIntoGraphemes($this->text);
        $numChars = count($chars);

        $lines              = [];
        $lastSeparator      = -1;
        $lastSeparatorCount = 0;
        $inHtml             = false;
        $inEscaped          = false;
        /** @var string[] $currLine */
        $currLine           = [];
        $currLineCount      = 0;

        for ($i = 0; $i < $numChars; $i++) {
            $currChar = $chars[$i];
            $currLine[] = $currChar;
            if ($inHtml) {
                if ($currChar === '>') {
                    $inHtml = false;
                }
            } elseif ($inEscaped) {
                if ($currChar === ';') {
                    $inEscaped = false;
                }
            } else {
                if ($currChar === '<' && ($chars[$i + 1] ?? '') === 'b' && ($chars[$i + 2] ?? '') === 'r' && ($chars[$i + 3] ?? '') === '>') {
                    array_pop($currLine);
                    $lines[] = implode('', $currLine) . '<br>';
                    $i += 3;
                    if (($chars[$i + 1] ?? '') === "\n") {
                        $i++;
                        $lines[count($lines) - 1] .= "\n";
                    }
                    $currLine      = [];
                    $currLineCount = 0;
                    continue;
                }
                if ($currChar === '<') {
                    $inHtml = true;
                    continue;
                }
                if ($currChar === '&') {
                    $inEscaped = true;
                }

                $currLineCount++;
                if ($currLineCount > $this->lineLength) {
                    if ($lastSeparator == -1) {
                        array_pop($currLine);
                        $lines[]       = implode('', $currLine) . '-';
                        $currLine      = [$currChar];
                        $currLineCount = 1;
                    } else {
                        if ($currChar === ' ') {
                            $lines[] = implode('', $currLine);

                            $currLine      = [];
                            $currLineCount = 0;
                        } else {
                            $lines[]  = implode('', array_slice($currLine, 0, $lastSeparator + 1));
                            $currLine = array_slice($currLine, $lastSeparator + 1);

                            $currLineCount = $this->lineLength - $lastSeparatorCount + 1;
                        }

                        $lastSeparator      = -1;
                        $lastSeparatorCount = 0;
                    }
                } elseif ($currChar === ' ' || $currChar === '-') {
                    $lastSeparator      = count($currLine) - 1;
                    $lastSeparatorCount = $currLineCount;
                }
            }
        }
        $currLine = implode('', $currLine);
        if (grapheme_strlen(trim($currLine)) > 0) {
            $lines[] = $currLine;
        }
        return $lines;
    }


    /**
     * @return string[]
     */
    private static function splitHtmlToLinesInt(\DOMElement $node, int $lineLength, string $prependLines): array
    {
        $indentedElements = ['ol', 'ul', 'pre', 'blockquote'];
        $veryBigElements  = ['h1', 'h2'];
        $bigElements      = ['h3', 'h4', 'h5', 'h6'];
        $out              = [];
        $inlineTextSpool  = '';
        foreach ($node->childNodes as $child) {
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                $inlineTextSpool .= Html::encode($child->data);
            } else {
                /** @var \DOMElement $child */
                if (in_array($child->nodeName, HTMLTools::KNOWN_BLOCK_ELEMENTS)) {
                    if ($inlineTextSpool != '') {
                        $spl = new LineSplitter($inlineTextSpool, $lineLength);
                        $arr = $spl->splitLines();
                        foreach ($arr as $newEl) {
                            $out[] = $prependLines . $newEl;
                        }

                        $inlineTextSpool = '';
                    }
                    if (in_array($child->nodeName, $veryBigElements)) {
                        $arr = self::splitHtmlToLinesInt($child, intval(floor($lineLength * 0.60)), $prependLines);
                    } elseif (in_array($child->nodeName, $bigElements)) {
                        $arr = self::splitHtmlToLinesInt($child, intval(floor($lineLength * 0.75)), $prependLines);
                    } elseif (in_array($child->nodeName, $indentedElements)) {
                        $arr = self::splitHtmlToLinesInt($child, $lineLength - 6, $prependLines);
                    } else {
                        $arr = self::splitHtmlToLinesInt($child, $lineLength, $prependLines);
                    }
                    foreach ($arr as $newEl) {
                        $out[] = $newEl;
                    }
                } else {
                    $inlineTextSpool .= HTMLTools::renderDomToHtml($child);
                }
            }
        }
        if ($inlineTextSpool != '') {
            $spl = new LineSplitter($inlineTextSpool, $lineLength);
            $arr = $spl->splitLines();
            foreach ($arr as $newEl) {
                $out[] = $prependLines . $newEl;
            }
        }

        if ($node->nodeName != 'body') {
            $open = '<' . $node->nodeName;
            foreach ($node->attributes as $key => $val) {
                $val = $node->getAttribute($key);
                $open .= ' ' . $key . '="' . Html::encode($val) . '"';
            }
            $open .= '>';
            if (count($out) > 0) {
                $out[0] = $open . $out[0];
                $out[count($out) - 1] .= '</' . $node->nodeName . '>';
            } else {
                $out[] = $open . '</' . $node->nodeName . '>';
            }
        }

        return $out;
    }

    /**
     * @return string[]
     */
    public static function splitHtmlToLines(string $html, int $lineLength, string $prependLines): array
    {
        $cache = HashedStaticCache::getInstance('splitHtmlToLines', [$html, $lineLength, $prependLines]);

        return $cache->getCached(function () use ($html, $lineLength, $prependLines) {
            $dom = HTMLTools::html2DOM($html);
            return self::splitHtmlToLinesInt($dom, $lineLength, $prependLines);
        });
    }

    public static function replaceLinebreakPlaceholdersByMarkup(string $html, bool $addLineNumbers, int $firstLineNo): string
    {
        $lineNo = $firstLineNo;
        $replacedHtml = preg_replace_callback('/###LINENUMBER###/sU', function () use (&$lineNo, $addLineNumbers) {
            $str = '###LINEBREAK###';
            if ($addLineNumbers) {
                $str .= '<span class="lineNumber" data-line-number="' . $lineNo . '" aria-hidden="true"></span>';
            }
            $lineNo++;

            return $str;
        }, $html);

        $blocks = implode("|", HTMLTools::KNOWN_BLOCK_ELEMENTS);
        $replacedHtml = preg_replace('/(<(' . $blocks . ')( [^>]*)?>)###LINEBREAK###/siu', '$1', $replacedHtml);
        return str_replace('###LINEBREAK###', '<br>', $replacedHtml);
    }

    public static function countMotionParaLines(string $para, int $lineLength): int
    {
        $lines = LineSplitter::splitHtmlToLines($para, $lineLength, '');
        return count($lines);
    }

    /*
     * HINT: This may or may not include the outer block nodes; specifically, if the first line within a list item is extracted,
     * the generated HTML could have the list formatting included. If the second line is extracted, it probably will not.
     * This function is mainly about the text content.
     */
    public static function extractLines(string $html, int $lineLength, int $paraFirstLineNo, int $lineFrom, int $lineTo): string
    {
        $sections = HTMLTools::sectionSimpleHTML($html, true);
        $lines = [];
        foreach ($sections as $section) {
            $lines = array_merge($lines, LineSplitter::splitHtmlToLines($section->html, $lineLength, ''));
        }
        $intLineFrom = $lineFrom - $paraFirstLineNo;
        $intLineTo = $lineTo - $paraFirstLineNo;
        $selectedLines = [];
        for ($i = $intLineFrom; $i <= $intLineTo && $i < count($lines); $i++) {
            $selectedLines[] = $lines[$i];
        }

        return trim(HTMLTools::correctHtmlErrors(implode('', $selectedLines)));
    }
}
