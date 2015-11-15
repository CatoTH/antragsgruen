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
        for ($i = 0; $i < count($parts); $i++) {
            if ($i == 0 && $parts[0] == '') {
                continue;
            }
            $out[] = [
                'text'     => ($i == 0 ? $parts[$i] : '###LINENUMBER###' . $parts[$i]),
                'lineFrom' => $firstLine,
                'lineTo'   => $firstLine,
                'newLine'  => true,
            ];
            $line++;
        }

        return $out;
    }


    /**
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
            if (preg_match_all('/<(\/?)(ins|del)( [^>]*)>/siu', $block['text'], $matches)) {
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
            if ($currBlock === null || $block['lineFrom'] > $currBlock['lineTo'] + 1) {
                if ($currBlock !== null) {
                    $groupedBlocks[] = $currBlock;
                }
                $currBlock = [
                    'text'     => '',
                    'lineFrom' => $block['lineFrom'],
                    'lineTo'   => $block['lineTo'],
                    'newLine'  => $block['newLine'],
                ];
            }
            if ($currBlock['text'] != '') {
                $currBlock['text'] .= '';
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

        $addToOut = function ($inlineHtml) use (&$out, $firstLine) {
            $lines    = static::splitToLines($inlineHtml, $firstLine);
            $affected = static::filterAffectedBlocks($lines);
            $grouped  = static::groupAffectedDiffBlocks($affected);
            for ($i = 0; $i < count($grouped); $i++) {
                $grouped[$i] = HTMLTools::correctHtmlErrors($grouped[$i]);
                $out[]       = $grouped[$i];
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

                    // @TODO If class=inserted|deleted, the whole element is added, no recursion

                    $arr = static::splitToAffectedLinesInt($child, $firstLine);
                    foreach ($arr as $newEl) {
                        $out[] = $newEl;
                    }
                } else {
                    $inlineTextSpool .= HTMLTools::renderDomToHtml($child);
                }
            }
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
        return static::splitToAffectedLinesInt($dom, $firstLine);
    }
}
