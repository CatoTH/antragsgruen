<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\exceptions\Internal;
use yii\helpers\Html;

class AffectedLinesFilter
{

    /**
     * @internal
     * @param string $string
     * @param int $firstLine
     * @return string[]
     */
    public static function splitToLines($string, $firstLine)
    {
        $out   = [];
        $line  = $firstLine;
        $parts = explode('###LINENUMBER###', $string);
        for ($i = 1; $i < count($parts); $i++) {
            $parts[$i] = '###LINENUMBER###' . $parts[$i];
        }
        if (strip_tags($parts[0]) == '' && count($parts) > 1) {
            $first    = array_shift($parts);
            $parts[0] = $first . $parts[0];
        }

        for ($i = 0; $i < count($parts); $i++) {
            if ($i == 0 && mb_strpos($parts[$i], '###LINENUMBER###') === false) {
                $line--;
            }
            $out[] = [
                'text'     => $parts[$i],
                'lineFrom' => $line,
                'lineTo'   => $line,
            ];
            $line++;
        }

        return $out;
    }


    /**
     * @internal
     * @param array $blocks
     * @return array
     * @throws Internal
     */
    public static function filterAffectedBlocks($blocks)
    {
        $inIns                 = $inDel = false;
        $affectedBlocks        = [];
        $middleUnchangedBlocks = [];

        foreach ($blocks as $blockNo => $block) {
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
                if (count($middleUnchangedBlocks) == 1 && $blockNo > 1) {
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
     * @internal
     * @param array $blocks
     * @return array
     */
    public static function groupAffectedDiffBlocks($blocks)
    {
        $currGroupedBlock     = null;
        $groupedBlocks = [];
        foreach ($blocks as $block) {
            $needsNewBlock = ($currGroupedBlock === null);
            if ($block['lineFrom'] > $currGroupedBlock['lineTo'] + 1) {
                $needsNewBlock = true;
            }
            $lineNumberPos = mb_strpos(strip_tags($block['text']), '###LINENUMBER###');
            if ($lineNumberPos === false || $lineNumberPos !== 0) {
                // This block was inserted => there is another line with the same number before
                if ($block['lineFrom'] > $currGroupedBlock['lineTo']) {
                    $needsNewBlock = true;
                }
            }
            if ($needsNewBlock) {
                if ($currGroupedBlock !== null) {
                    $groupedBlocks[] = $currGroupedBlock;
                }
                $currGroupedBlock = [
                    'text'     => '',
                    'lineFrom' => $block['lineFrom'],
                    'lineTo'   => $block['lineTo'],
                ];
            }
            $currGroupedBlock['text'] .= $block['text'];

            if ($block['lineTo'] > $currGroupedBlock['lineTo']) {
                // This is the normal case; there are some rare cases (see testLiSplitIntoTwo test case)
                // in which the new block does not have line numbers and therefore a lower lineTo
                $currGroupedBlock['lineTo'] = $block['lineTo'];
            }
        }
        if ($currGroupedBlock) {
            $groupedBlocks[] = $currGroupedBlock;
        }
        return $groupedBlocks;
    }


    /**
     * @param \DOMElement $node
     * @param int $firstLine
     * @return array
     */
    private static function splitToAffectedLinesInt(\DOMElement $node, $firstLine)
    {
        $out             = [];
        $inlineTextSpool = '';
        $currLine        = $firstLine;

        $addToOut = function ($inlineHtml, $currLine) use (&$out) {
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
                        $addToOut($inlineTextSpool, $currLine);
                        $inlineTextSpool = '';
                    }

                    $classes = ($child->getAttribute('class') ? $child->getAttribute('class') : '');
                    $classes = explode(' ', $classes);
                    if (in_array('inserted', $classes) || in_array('deleted', $classes)) {
                        $inlineHtml = HTMLTools::renderDomToHtml($child);
                        $lines      = static::splitToLines($inlineHtml, $currLine);
                        $grouped    = static::groupAffectedDiffBlocks($lines);
                        $out        = array_merge($out, $grouped);
                    } else {
                        $arr = static::splitToAffectedLinesInt($child, $currLine);
                        foreach ($arr as $newEl) {
                            $out[] = $newEl;
                        }
                    }
                    $currLine += mb_substr_count($child->nodeValue, '###LINENUMBER###');
                } else {
                    $inlineTextSpool .= HTMLTools::renderDomToHtml($child);
                }
            }
        }

        if ($inlineTextSpool != '') {
            $addToOut($inlineTextSpool, $currLine);
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
        // <del>###LINENUMBER### would mark the previous line as affected as well
        $html = str_replace('<del>###LINENUMBER###', '###LINENUMBER###<del>', $html);

        $dom = HTMLTools::html2DOM($html);
        if (is_a($dom, \DOMText::class)) {
            return [];
        }
        /** @var \DOMElement $dom */
        $lines = static::splitToAffectedLinesInt($dom, $firstLine);
        $grouped = static::groupAffectedDiffBlocks($lines);
        return $grouped;
    }
}
