<?php

namespace app\components\diff;

use app\components\diff\DataTypes\AffectedLineBlock;
use app\components\HTMLTools;
use yii\helpers\Html;

class AffectedLinesFilter
{
    /**
     * @return AffectedLineBlock[]
     * @internal
     */
    public static function splitToLines(string $string, int $firstLine): array
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
            if ($i == 0 && !str_contains($parts[$i], '###LINENUMBER###')) {
                $line--;
            }

            $affected = new AffectedLineBlock();
            $affected->text = $parts[$i];
            $affected->lineFrom = $line;
            $affected->lineTo = $line;
            $out[] = $affected;
            $line++;
        }

        return $out;
    }

    /**
     * @param AffectedLineBlock[] $blocks
     * @return AffectedLineBlock[]
     */
    public static function filterAffectedBlocks(array $blocks, int $context = 0): array
    {
        $inIns          = $inDel = false;
        $affectedBlocks = [];

        $unchangedBeforeNextSpool = [];
        $unchangedAfterLastSpool = [];

        foreach ($blocks as $block) {
            if ($block->text === '') {
                continue;
            }
            $hadDiff = false;
            if (preg_match_all('/<(\/?)(ins|del)( [^>]*)?>/siu', $block->text, $matches)) {
                $hadDiff = true;
                for ($i = 0; $i < count($matches[0]); $i++) {
                    if ($matches[1][$i] === '' && $matches[2][$i] === 'ins') {
                        $inIns = true;
                    } elseif ($matches[1][$i] === '/' && $matches[2][$i] === 'ins') {
                        $inIns = false;
                    } elseif ($matches[1][$i] === '' && $matches[2][$i] === 'del') {
                        $inDel = true;
                    } elseif ($matches[1][$i] === '/' && $matches[2][$i] === 'del') {
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
                $affectedBlocks   = array_merge($affectedBlocks, $unchangedAfterLastSpool);
                $affectedBlocks   = array_merge($affectedBlocks, $unchangedBeforeNextSpool);
                $affectedBlocks[] = $block;

                $unchangedAfterLastSpool  = [];
                $unchangedBeforeNextSpool = [];
            } else {
                if (count($unchangedAfterLastSpool) < $context && count($affectedBlocks) > 0) {
                    // unchangedAfterLastSpool will immediately be filled after a change (but only after the first change)
                    // and always be appended
                    $unchangedAfterLastSpool[] = $block;
                } else {
                    // unchangedBeforeNextSpool always holds the last $context lines
                    $unchangedBeforeNextSpool[] = $block;
                    while (count($unchangedBeforeNextSpool) > $context) {
                        array_shift($unchangedBeforeNextSpool);
                    }
                }
            }
        }

        return array_merge($affectedBlocks, $unchangedAfterLastSpool);
    }

    /**
     * @param AffectedLineBlock[] $blocks
     * @return AffectedLineBlock[]
     */
    public static function groupAffectedDiffBlocks(array $blocks): array
    {
        $currGroupedBlock = null;
        $groupedBlocks    = [];
        foreach ($blocks as $block) {
            if ($currGroupedBlock) {
                $needsNewBlock = false;
                if ($block->lineFrom > $currGroupedBlock->lineTo + 1) {
                    $needsNewBlock = true;
                }
                $lineNumberPos = mb_strpos(strip_tags($block->text), '###LINENUMBER###');
                if ($lineNumberPos === false || $lineNumberPos !== 0) {
                    // This block was inserted => there is another line with the same number before
                    if ($block->lineFrom > $currGroupedBlock->lineTo) {
                        $needsNewBlock = true;
                    }
                }
            } else {
                $needsNewBlock = true;
            }
            if ($needsNewBlock) {
                if ($currGroupedBlock !== null) {
                    $groupedBlocks[] = $currGroupedBlock;
                }
                $currGroupedBlock = new AffectedLineBlock();
                $currGroupedBlock->text = '';
                $currGroupedBlock->lineFrom = $block->lineFrom;
                $currGroupedBlock->lineTo = $block->lineTo;
            }
            $currGroupedBlock->text .= $block->text;

            if ($block->lineTo > $currGroupedBlock->lineTo) {
                // This is the normal case; there are some rare cases (see testLiSplitIntoTwo test case)
                // in which the new block does not have line numbers and therefore a lower lineTo
                $currGroupedBlock->lineTo = $block->lineTo;
            }
        }
        if ($currGroupedBlock) {
            $groupedBlocks[] = $currGroupedBlock;
        }

        return $groupedBlocks;
    }


    /**
     * @return AffectedLineBlock[]
     */
    private static function splitToAffectedLinesInt(\DOMElement $node, int $firstLine, int $context): array
    {
        $out             = [];
        $inlineTextSpool = '';
        $currLine        = $firstLine;

        $addToOut = function ($inlineHtml, $currLine) use (&$out, $context) {
            $lines    = self::splitToLines($inlineHtml, $currLine);
            $affected = self::filterAffectedBlocks($lines, $context);
            $grouped  = self::groupAffectedDiffBlocks($affected);

            for ($i = 0; $i < count($grouped); $i++) {
                $grouped[$i]->text = HTMLTools::correctHtmlErrors($grouped[$i]->text);
                $out[] = $grouped[$i];
            }
        };

        foreach ($node->childNodes as $child) {
            if (is_a($child, \DOMText::class)) {
                /** @var \DOMText $child */
                $inlineTextSpool .= $child->data;
            } else {
                /** @var \DOMElement $child */
                if (in_array($child->nodeName, HTMLTools::KNOWN_BLOCK_ELEMENTS)) {
                    if ($inlineTextSpool !== '') {
                        $addToOut($inlineTextSpool, $currLine);
                        $inlineTextSpool = '';
                    }

                    $classes = ($child->getAttribute('class') ? $child->getAttribute('class') : '');
                    $classes = explode(' ', $classes);
                    if (in_array('inserted', $classes) || in_array('deleted', $classes)) {
                        $inlineHtml = HTMLTools::renderDomToHtml($child);
                        $lines      = self::splitToLines($inlineHtml, $currLine);
                        $grouped    = self::groupAffectedDiffBlocks($lines);
                        $out        = array_merge($out, $grouped);
                    } else {
                        $arr = self::splitToAffectedLinesInt($child, $currLine, $context);
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

        if ($inlineTextSpool !== '') {
            $addToOut($inlineTextSpool, $currLine);
        }

        if ($node->nodeName !== 'body') {
            $open = '<' . $node->nodeName;
            foreach ($node->attributes as $key => $val) {
                $val  = $node->getAttribute($key);
                $open .= ' ' . $key . '="' . Html::encode($val) . '"';
            }
            $open .= '>';
            for ($i = 0; $i < count($out); $i++) {
                $out[$i]->text = $open . $out[$i]->text . '</' . $node->nodeName . '>';
            }
        }

        return $out;
    }

    /**
     * @return  AffectedLineBlock[]
     */
    public static function splitToAffectedLines(string $html, int $firstLine, int $context = 0): array
    {
        // <del>###LINENUMBER### would mark the previous line as affected as well
        $html = str_replace('<del>###LINENUMBER###', '###LINENUMBER###<del>', $html);

        $dom = HTMLTools::html2DOM($html);
        if (is_a($dom, \DOMText::class)) {
            return [];
        }
        $lines = self::splitToAffectedLinesInt($dom, $firstLine, $context);

        return self::groupAffectedDiffBlocks($lines);
    }
}
