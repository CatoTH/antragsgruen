<?php

namespace app\components;

use yii\helpers\Html;

class LineSplitter
{

    private $lineLength;
    private $text;

    /**
     * @param string $text
     * @param int $lineLength
     */
    public function __construct($text, $lineLength)
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
    public function splitLines()
    {
        $lines              = [];
        $lastSeparator      = -1;
        $lastSeparatorCount = 0;
        $inHtml             = false;
        $inEscaped          = false;
        $currLine           = '';
        $currLineCount      = 0;

        for ($i = 0; $i < mb_strlen($this->text); $i++) {
            $currChar = mb_substr($this->text, $i, 1);
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
                if (mb_substr($this->text, $i, 4) == '<br>') {
                    $lines[] = mb_substr($currLine, 0, mb_strlen($currLine) - 1) . '<br>';
                    $i += 3;
                    if (mb_substr($this->text, $i + 1, 1) == "\n") {
                        $i++;
                        $lines[count($lines) - 1] .= "\n";
                    }
                    $currLine      = '';
                    $currLineCount = 1;
                    continue;
                }
                if ($currChar == '<') {
                    $inHtml = true;
                    continue;
                }
                if ($currChar == '&') {
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
                        $lines[]       = mb_substr($currLine, 0, mb_strlen($currLine) - 1) . '-';
                        $currLine      = $currChar;
                        $currLineCount = 1;
                    } else {
                        /*
                        echo "Aktuelles Zeichen: \"" . mb_substr($this->text, $i, 1) . "\"\n";
                        */
                        if (mb_substr($this->text, $i, 1) == ' ') {
                            $lines[] = $currLine;

                            $currLine      = '';
                            $currLineCount = 0;
                        } else {
                            $remainder = mb_substr($currLine, $lastSeparator + 1);
                            /*
                            echo "Überhang: \"" . $ueberhang . "\"\n";
                            echo "Letztes ist Leerzeichen: " . $lastIsSpace . "\n";
                            */
                            $lines[] = mb_substr($currLine, 0, $lastSeparator + 1);

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
                    $lastSeparator      = mb_strlen($currLine) - 1;
                    $lastSeparatorCount = $currLineCount;
                }
            }
        }
        if (mb_strlen(trim($currLine)) > 0) {
            $lines[] = $currLine;
        }
        return $lines;
    }


    /**
     * @param \DOMElement $node
     * @param int $lineLength
     * @param string $prependLines
     * @return string[]
     */
    private static function splitHtmlToLinesInt(\DOMElement $node, $lineLength, $prependLines)
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
                if (in_array($child->nodeName, HTMLTools::$KNOWN_BLOCK_ELEMENTS)) {
                    if ($inlineTextSpool != '') {
                        $spl = new static($inlineTextSpool, $lineLength);
                        $arr = $spl->splitLines();
                        foreach ($arr as $newEl) {
                            $out[] = $prependLines . $newEl;
                        }

                        $inlineTextSpool = '';
                    }
                    if (in_array($child->nodeName, $veryBigElements)) {
                        $arr = static::splitHtmlToLinesInt($child, floor($lineLength * 0.60), $prependLines);
                    } elseif (in_array($child->nodeName, $bigElements)) {
                        $arr = static::splitHtmlToLinesInt($child, floor($lineLength * 0.75), $prependLines);
                    } elseif (in_array($child->nodeName, $indentedElements)) {
                        $arr = static::splitHtmlToLinesInt($child, $lineLength - 6, $prependLines);
                    } else {
                        $arr = static::splitHtmlToLinesInt($child, $lineLength, $prependLines);
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
            $spl = new static($inlineTextSpool, $lineLength);
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
     * @param string $html
     * @param int $lineLength
     * @param string $prependLines
     * @return string[]
     */
    public static function splitHtmlToLines($html, $lineLength, $prependLines)
    {
        $cache_depends = [$html, $lineLength, $prependLines];
        $cache = HashedStaticCache::getCache('splitHtmlToLines', $cache_depends);
        if ($cache) {
            return $cache;
        }

        $dom = HTMLTools::html2DOM($html);
        if (is_a($dom, \DOMText::class)) {
            $spl = new static($html, $lineLength);
            $result = $spl->splitLines();
        } else {
            /** @var \DOMElement $dom */
            $result = static::splitHtmlToLinesInt($dom, $lineLength, $prependLines);
        }

        HashedStaticCache::setCache('splitHtmlToLines', $cache_depends, $result);
        return $result;
    }

    /**
     * @param string[] $paragraphs
     * @param int $lineLength
     * @return \string[]
     */
    public static function addLineNumbersToParagraphs($paragraphs, $lineLength)
    {
        for ($i = 0; $i < count($paragraphs); $i++) {
            $lines          = static::splitHtmlToLines($paragraphs[$i], $lineLength, '###LINENUMBER###');
            $paragraphs[$i] = implode('', $lines);
        }
        return $paragraphs;
    }


    /**
     * @param string $para
     * @param int $lineLength
     * @return int
     */
    public static function countMotionParaLines($para, $lineLength)
    {
        $lines = LineSplitter::splitHtmlToLines($para, $lineLength, '');
        return count($lines);
    }
}
