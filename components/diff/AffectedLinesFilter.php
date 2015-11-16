<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\exceptions\Internal;
use yii\helpers\Html;

class AffectedLinesFilter
{
    /**
     * @param string $string
     * @param int $firstLine
     * @return string[]
     */
    public static function splitToLines($string, $firstLine)
    {
        $out   = [];
        $line  = $firstLine;
        $parts = explode('###LINENUMBER###', $string);
        if (strip_tags($parts[0]) == '') {
            $firstLine++;
        }
        for ($i = 0; $i < count($parts); $i++) {
            if ($i == 0 && $parts[0] == '') {
                continue;
            }
            $out[] = [
                'text'     => ($i == 0 ? $parts[$i] : '###LINENUMBER###' . $parts[$i]),
                'lineFrom' => $firstLine,
                'lineTo'   => $firstLine,
            ];
            $line++;
        }

        return $out;
    }


    /**
     * @param array $blocks
     * @return array
     * @throws Internal
     */
    public static function filterAffectedBlocks($blocks)
    {
        $inIns                 = $inDel = false;
        $affectedBlocks        = [];
        $middleUnchangedBlocks = [];

        foreach ($blocks as $block) {
            if ($block['text'] == '') {
                continue;
            }
            $hadDiff = false;
            if (preg_match_all('/<(\/?)(ins|del)( [^>]*)?>/siu', $block['text'], $matches)) {
                $hadDiff = true;
                for ($i = 0; $i < count($matches[0]); $i++) {
                    if ($matches[1][$i] == '' && $matches[2][$i] == 'ins') {
                        $inIns = true;
                    } elseif ($matches[1][$i] == '/' && $matches[2][$i] == 'ins') {
                        $inIns = false;
                    } elseif ($matches[1][$i] == '' && $matches[2][$i] == 'del') {
                        $inDel = true;
                    } elseif ($matches[1][$i] == '/' && $matches[2][$i] == 'del') {
                        $inDel = false;
                    }
                }
            }

            $addBlock = false;
            if ($inIns) {
                $addBlock = true;
            } elseif ($inDel) {
                $addBlock = true;
            } elseif ($hadDiff) {
                $addBlock = true;
            }
            if ($addBlock) {
                if (count($middleUnchangedBlocks) == 1) {
                    $affectedBlocks[] = $middleUnchangedBlocks[0];
                }
                $affectedBlocks[]      = $block;
                $middleUnchangedBlocks = [];
            } else {
                $middleUnchangedBlocks[] = $block;
            }
        }
        return $affectedBlocks;
    }

    /**
     * @param array $blocks
     * @return array
     */
    public static function groupAffectedDiffBlocks($blocks)
    {
        $currBlock     = null;
        $groupedBlocks = [];
        foreach ($blocks as $block) {
            $needsNewBlock = ($currBlock === null);
            if ($block['lineFrom'] > $currBlock['lineTo'] + 1) {
                $needsNewBlock = true;
            }
            $lineNumberPos = mb_strpos(strip_tags($block['text']), '###LINENUMBER###');
            if ($lineNumberPos === false || $lineNumberPos !== 0) {
                // This block was inserted => there is another line with the same number before
                if ($block['lineFrom'] > $currBlock['lineTo']) {
                    $needsNewBlock = true;
                }
            }
            if ($needsNewBlock) {
                if ($currBlock !== null) {
                    $groupedBlocks[] = $currBlock;
                }
                $currBlock = [
                    'text'     => '',
                    'lineFrom' => $block['lineFrom'],
                    'lineTo'   => $block['lineTo'],
                ];
            }
            $currBlock['text'] .= $block['text'];
            $currBlock['lineTo'] = $block['lineTo'];
        }
        if ($currBlock) {
            $groupedBlocks[] = $currBlock;
        }
        return $groupedBlocks;
    }


    /**
     * @param \DOMElement $node
     * @param int $firstLine
     * @return array
     */
    public static function splitToAffectedLinesInt(\DOMElement $node, $firstLine)
    {
        $out             = [];
        $inlineTextSpool = '';
        $currLine = $firstLine;

        $addToOut = function ($inlineHtml) use (&$out, $currLine) {
            $lines    = static::splitToLines($inlineHtml, $currLine);
            $affected = static::filterAffectedBlocks($lines);
            $grouped  = static::groupAffectedDiffBlocks($affected);

            for ($i = 0; $i < count($grouped); $i++) {
                $grouped[$i]['text'] = HTMLTools::correctHtmlErrors($grouped[$i]['text']);
                $out[]               = $grouped[$i];
            }
        };

        foreach ($node->childNodes as $child) {
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                $inlineTextSpool .= $child->data;
            } else {
                /** @var \DOMElement $child */
                if (in_array($child->nodeName, HTMLTools::$KNOWN_BLOCK_ELEMENTS)) {
                    if ($inlineTextSpool != '') {
                        $addToOut($inlineTextSpool);
                        $inlineTextSpool = '';
                    }

                    $classes = ($child->getAttribute('class') ? $child->getAttribute('class') : '');
                    $classes = explode(' ', $classes);
                    if (in_array('inserted', $classes) || in_array('deleted', $classes)) {
                        $inlineHtml = HTMLTools::renderDomToHtml($child);
                        $lines      = static::splitToLines($inlineHtml, $currLine);
                        $grouped    = static::groupAffectedDiffBlocks($lines);
                        $out                    = array_merge($out, $grouped);
                    } else {
                        $arr = static::splitToAffectedLinesInt($child, $currLine);
                        foreach ($arr as $newEl) {
                            $out[] = $newEl;
                        }
                    }
                } else {
                    $inlineTextSpool .= HTMLTools::renderDomToHtml($child);
                }
            }
            $currLine += mb_substr_count($child->nodeValue, '###LINENUMBER###');
        }

        if ($inlineTextSpool != '') {
            $addToOut($inlineTextSpool);
        }

        if ($node->nodeName != 'body') {
            $open = '<' . $node->nodeName;
            foreach ($node->attributes as $key => $val) {
                $val = $node->getAttribute($key);
                $open .= ' ' . $key . '="' . Html::encode($val) . '"';
            }
            $open .= '>';
            for ($i = 0; $i < count($out); $i++) {
                $out[$i]['text'] = $open . $out[$i]['text'] . '</' . $node->nodeName . '>';
            }
        }

        return $out;
    }

    /**
     * @param string $html
     * @param int $firstLine
     * @return array
     */
    public static function splitToAffectedLines($html, $firstLine)
    {
        $dom = HTMLTools::html2DOM($html);
        if (is_a($dom, \DOMText::class)) {
            return [];
        }
        /** @var \DOMElement $dom */
        $lines   = static::splitToAffectedLinesInt($dom, $firstLine);
        $grouped = static::groupAffectedDiffBlocks($lines);
        return $grouped;
    }
}
