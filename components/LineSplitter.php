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
                    $lines[] = mb_substr($currLine, 0, mb_strlen($currLine) - 1) . '###FORCELINEBREAK###';
                    $i += 3;
                    if (mb_substr($this->text, $i + 1, 1) == "\n") {
                        $i++;
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
        $blockElements    = ['div', 'p', 'ol', 'ul', 'li', 'section', 'pre', 'blockquote'];
        $indentedElements = ['ol', 'ul', 'pre', 'blockquote'];
        $out              = [];
        $inlineTextSpool  = '';
        foreach ($node->childNodes as $child) {
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                $inlineTextSpool .= $child->data;
            } else {
                /** @var \DOMElement $child */
                if (in_array($child->nodeName, $blockElements)) {
                    if ($inlineTextSpool != '') {
                        $spl = new static($inlineTextSpool, $lineLength);
                        $arr = $spl->splitLines();
                        foreach ($arr as $newEl) {
                            $out[] = $prependLines . $newEl;
                        }

                        $inlineTextSpool = '';
                    }
                    if (in_array($child->nodeName, $indentedElements)) {
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
     * @return string
     */
    public static function splitHtmlToLines($html, $lineLength, $prependLines)
    {
        $dom = HTMLTools::html2DOM($html);
        if (is_a($dom, \DOMText::class)) {
            $spl = new static($html, $lineLength);
            return $spl->splitLines();
        } else {
            /** @var \DOMElement $dom */
            return static::splitHtmlToLinesInt($dom, $lineLength, $prependLines);
        }
    }


    /**
     * @deprecated
     * Return value contains ###FORCELINEBREAK###
     *
     * @param string $para
     * @param bool $lineNumbers
     * @param int $lineLength
     * @return string[]
     */
    public static function motionPara2lines($para, $lineNumbers, $lineLength)
    {
        if (!defined('YII_DEBUG') || !YII_DEBUG) {
            $cacheKey = [md5($para), $lineNumbers, $lineLength];
            if ($cached = \yii::$app->cache->get($cacheKey)) {
                return $cached;
            }
        }

        if (mb_stripos($para, '<ul>') === 0 || mb_stripos($para, '<ol>') === 0 ||
            mb_stripos($para, '<blockquote>') === 0 || mb_stripos($para, '<pre>') === 0
        ) {
            $lineLength -= 6;
        }
        $splitter = new LineSplitter($para, $lineLength);
        $linesIn  = $splitter->splitLines();

        if ($lineNumbers) {
            $linesOut = [];
            $pres     = ['<p>', '<ul><li>', '<ol( start="[0-9]+")?><li>', '<blockquote><p>', '<pre>'];
            $linePre  = '###LINENUMBER###';
            foreach ($linesIn as $line) {
                $inserted = false;
                foreach ($pres as $pre) {
                    if (preg_match('/^' . $pre . '/siu', $line, $matches)) {
                        $inserted = true;
                        $line     = str_ireplace($matches[0], $matches[0] . $linePre, $line);
                    }
                }
                if (!$inserted) {
                    $line = $linePre . $line;
                }
                $linesOut[] = $line;
            }
        } else {
            $linesOut = $linesIn;
        }

        if (!defined('YII_DEBUG') || !YII_DEBUG) {
            \yii::$app->cache->set($cacheKey, $linesOut, 7 * 24 * 3600);
        }

        return $linesOut;
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
