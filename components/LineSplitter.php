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
     * Forced line breaks are marked by a trailing ###FORCELINEBREAK###
     *
     * @static
     * @return string[]
     */
    public function splitLines(): array
    {
        $lines              = [];
        $lastSeparator      = -1;
        $lastSeparatorCount = 0;
        $inHtml             = false;
        $inEscaped          = false;
        $currLine           = '';
        $currLineCount      = 0;

        for ($i = 0; $i < grapheme_strlen($this->text); $i++) {
            $currChar = (string)grapheme_substr($this->text, $i, 1);
            $currLine .= $currChar;
            if ($inHtml) {
                if ($currChar == '>') {
                    $inHtml = false;
                }
            } elseif ($inEscaped) {
                if ($currChar == ';') {
                    $inEscaped = false;
                }
            } else {
                if (grapheme_substr($this->text, $i, 4) === '<br>') {
                    $lines[] = (string)grapheme_substr($currLine, 0, grapheme_strlen($currLine) - 1) . '<br>';
                    $i += 3;
                    if (grapheme_substr($this->text, $i + 1, 1) === "\n") {
                        $i++;
                        $lines[count($lines) - 1] .= "\n";
                    }
                    $currLine      = '';
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
                    /*
                    echo "Aktuelle Zeile: \"" . $currLine . "\"\n";
                    echo "Count: \"" . $currLineCount . "\"\n";
                    echo "Letztes Leerzeichen: \"" . $lastSeparator . "\"\n";
                    */
                    if ($lastSeparator == -1) {
                        $lines[]       = grapheme_substr($currLine, 0, grapheme_strlen($currLine) - 1) . '-';
                        $currLine      = $currChar;
                        $currLineCount = 1;
                    } else {
                        /*
                        echo "Aktuelles Zeichen: \"" . grapheme_substr($this->text, $i, 1) . "\"\n";
                        */
                        if (grapheme_substr($this->text, $i, 1) == ' ') {
                            $lines[] = $currLine;

                            $currLine      = '';
                            $currLineCount = 0;
                        } else {
                            $remainder = (string)grapheme_substr($currLine, $lastSeparator + 1);
                            /*
                            echo "Überhang: \"" . $ueberhang . "\"\n";
                            echo "Letztes ist Leerzeichen: " . $lastIsSpace . "\n";
                            */
                            $lines[] = (string)grapheme_substr($currLine, 0, $lastSeparator + 1);

                            $currLine      = $remainder;
                            $currLineCount = $this->lineLength - $lastSeparatorCount + 1;
                        }

                        $lastSeparator      = -1;
                        $lastSeparatorCount = 0;
                    }
                    /*
                    echo "Neue aktuelle Zeile: \"" . $currLine . "\"\n";
                    echo "Count: \"" . $currLineCount . "\"\n\n";
                    */
                } elseif (in_array($currChar, [' ', '-'])) {
                    $lastSeparator      = grapheme_strlen($currLine) - 1;
                    $lastSeparatorCount = $currLineCount;
                }
            }
        }
        if (grapheme_strlen(trim((string)$currLine)) > 0) {
            $lines[] = (string)$currLine;
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
            if (is_a($dom, \DOMText::class)) {
                $spl = new LineSplitter($html, $lineLength);

                return $spl->splitLines();
            } else {
                return self::splitHtmlToLinesInt($dom, $lineLength, $prependLines);
            }
        });
    }

    /**
     * @param string[] $paragraphs
     * @return string[]
     */
    public static function addLineNumbersToParagraphs(array $paragraphs, int $lineLength): array
    {
        for ($i = 0; $i < count($paragraphs); $i++) {
            $lines          = self::splitHtmlToLines($paragraphs[$i], $lineLength, '###LINENUMBER###');
            $paragraphs[$i] = implode('', $lines);
        }
        return $paragraphs;
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
