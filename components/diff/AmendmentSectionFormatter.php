<?php
namespace app\components\diff;

use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\db\AmendmentSection;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

class AmendmentSectionFormatter
{
    /** @var string[] */
    private $sectionsOriginal;
    private $sectionsNew;

    /** @var int */
    private $firstLine = 0;

    /** @var bool */
    private $debug = false;

    /**
     * @param string $text
     */
    public function setTextOriginal($text)
    {
        $this->sectionsOriginal = HTMLTools::sectionSimpleHTML($text);
    }

    /**
     * @param string $text
     */
    public function setTextNew($text)
    {
        $this->sectionsNew = HTMLTools::sectionSimpleHTML($text);
    }

    /**
     * @param int $lineNo
     */
    public function setFirstLineNo($lineNo)
    {
        $this->firstLine = $lineNo;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param string $text
     * @param int $lineLength
     * @return string
     */
    public static function addLineNumberPlaceholders($text, $lineLength)
    {
        $linesOut = LineSplitter::motionPara2lines($text, true, $lineLength);
        return implode('', $linesOut);
    }


    /**
     * Called from getDiffLinesWithNumbers
     *
     * @param array $blocks
     *     The whole section text. Standard text is split to blocks by line,
     *     Lists are split to blocks by list point. Format:
     *     array(28) {
     * ...
     * [8]=>
     * array(4) {
     * ["text"]=> string(46) "<ul class="inserted"><li>Neuer Punkt</li></ul>"
     * ["lineFrom"]=> int(8)
     * ["lineTo"]=> int(8)
     * ["newLine"]=> bool(true)
     * }
     * [9]=>
     * array(4) {
     * ["text"]=> string(105) "<ul><li>Do nackata Wurscht i hob di narrisch gean</li></ul>"
     * ["lineFrom"]=> int(9)
     * ["lineTo"]=> int(10)
     * ["newLine"]=> bool(false)
     * }
     * ...
     * }
     * @return array
     * @throws Internal
     */
    public static function filterAffectedBlocks($blocks)
    {
        $inIns                 = $inDel = false;
        $affectedBlocks        = [];
        $middleUnchangedBlocks = [];

        foreach ($blocks as $block) {
            $hadDiff = false;
            if ($inIns) {
                $block['text'] = '<ins>' . $block['text'];
            }
            if ($inDel) {
                $block['text'] = '<del>' . $block['text'];
            }
            if (preg_match_all('/<\/?(ins|del)>/siu', $block['text'], $matches)) {
                $hadDiff = true;
                foreach ($matches[0] as $found) {
                    switch ($found) {
                        case '<ins>':
                            $inIns = true;
                            break;
                        case '</ins>':
                            $inIns = false;
                            break;
                        case '<del>':
                            $inDel = true;
                            break;
                        case '</del>':
                            $inDel = false;
                            break;
                        default:
                            throw new Internal('Unknown token: ' . $found[0]);
                    }
                }
            }

            $addBlock = false;
            if ($inIns) {
                $block['text'] = $block['text'] . '</ins>';
                $addBlock      = true;
            } elseif ($inDel) {
                $block['text'] = $block['text'] . '</del>';
                $addBlock      = true;
            } elseif ($hadDiff) {
                $addBlock = true;
            } else {
                foreach (['ul', 'ol', 'pre', 'blockquote', 'div', 'p'] as $tag) {
                    if (preg_match('/<(' . $tag . ') class="inserted">.*<\/(' . $tag . ')>/siuU', $block['text'])) {
                        $addBlock = true;
                    }
                    if (preg_match('/<(' . $tag . ') class="deleted">.*<\/(' . $tag . ')>/siuU', $block['text'])) {
                        $addBlock = true;
                    }
                }
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
     * @param string $computed
     * @return array
     */
    public static function getDiffSplitToLines($computed)
    {
        $blockElements = 'ul|blockquote|ol|pre';
        /*
        $computed      = preg_replace_callback('/<pre[^>]*>.*<\/pre>/siuU', function ($matches) {
            return str_replace("\n", '###FORCELINEBREAK###', $matches[0]);
        }, $computed);
        */

        $computed = preg_replace('/<\/(' . $blockElements . '|p|div|pre)>/siu', '\0' . "\n", $computed);
        $lines    = explode("\n", $computed);

        /*
        for ($i = 0; $i < count($lines) - 1; $i++) {
            $last5  = mb_substr($lines[$i], mb_strlen($lines[$i]) - 5);
            $first6 = mb_substr($lines[$i + 1], 0, 6);
            if ($last5 == '<ins>' && $first6 == '</ins>') {
                $lines[$i] .= '###FORCELINEBREAK###</ins>';
                $lines[$i + 1] = mb_substr($lines[$i + 1], 6);
            }
            if ($last5 == '<del>' && $first6 == '</del>') {
                $lines[$i] .= '###FORCELINEBREAK###</del>';
                $lines[$i + 1] = mb_substr($lines[$i + 1], 6);
            }
        }
        */

        $out = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if ($line == '') {
                continue;
            }
            if (preg_match('/^(<div[^>]*>)?<(' . $blockElements . ')/siu', $line)) {
                $out[] = $line;
            } else {
                $parts         = explode('###LINENUMBER###', $line);
                for ($j = 1; $j < count($parts); $j++) {
                    $parts[$j] = '###LINENUMBER###' . $parts[$j];
                }
                $dangling      = '';
                foreach ($parts as $j => $part) {
                    if ($part != '' || $j > 0) {
                        if ($part == '<ins>' || $part == '<del>') {
                            $dangling = $part;
                        } else {
                            $out[]    = $dangling . $part;
                            $dangling = '';
                        }
                    }
                }
            }
        }
        return $out;
    }

    /**
     * @param string $htmlDiff
     * @param int $lineOffset
     * @return array
     */
    public static function htmlDiff2LineBlocks($htmlDiff, $lineOffset)
    {
        $htmlDiff      = preg_replace('/<p class="inserted">(.*)<\/p>/siuU', '<p><ins>$1</ins></p>', $htmlDiff);
        $htmlDiff      = preg_replace('/<p class="deleted">(.*)<\/p>/siuU', '<p><del>$1</del></p>', $htmlDiff);
        $htmlDiff      = preg_replace('/<div class="inserted">(.*)<\/div>/siuU', '<div><ins>$1</ins></div>', $htmlDiff);
        $htmlDiff      = preg_replace('/<div class="deleted">(.*)<\/div>/siuU', '<div><del>$1</del></div>', $htmlDiff);
        $htmlDiff      = preg_replace('/<pre class="inserted">(.*)<\/pre>/siuU', '<pre><ins>$1</ins></pre>', $htmlDiff);
        $htmlDiff      = preg_replace('/<pre class="deleted">(.*)<\/pre>/siuU', '<pre><del>$1</del></pre>', $htmlDiff);
        $htmlDiff      = preg_replace('/<blockquote class="inserted">(.*)<\/blockquote>/siuU', '<blockquote><ins>$1</ins></blockquote>', $htmlDiff);
        $htmlDiff      = preg_replace('/<blockquote class="deleted">(.*)<\/blockquote>/siuU', '<blockquote><del>$1</del></blockquote>', $htmlDiff);
        $computedLines = static::getDiffSplitToLines($htmlDiff);

        $lineNo = 0;
        $blocks = [];
        foreach ($computedLines as $line) {
            $substrcount            = mb_substr_count($line, '###LINENUMBER###');
            $computedLines[$lineNo] = str_replace('###LINENUMBER###', '', $line);
            if ($substrcount == 0 && strip_tags($line) != '') {
                // Inserted list point
                $blocks[] = [
                    'text'     => str_replace('###LINENUMBER###', '', $line),
                    'lineFrom' => $lineOffset + $lineNo - 1,
                    'lineTo'   => $lineOffset + $lineNo - 1,
                    'newLine'  => true,
                ];
            } else {
                $blocks[] = [
                    'text'     => str_replace('###LINENUMBER###', '', $line),
                    'lineFrom' => $lineOffset + $lineNo,
                    'lineTo'   => $lineOffset + $lineNo + $substrcount - 1,
                    'newLine'  => false,
                ];
            }

            $lineNo += $substrcount;
        }
        return $blocks;
    }

    /**
     * @param int $lineLength
     * @param int $diffFormatting
     * @param bool $grouped
     * @return array[]
     * @throws Internal
     */
    public function getDiffLinesWithNumbers($lineLength, $diffFormatting, $grouped = true)
    {
        try {
            $originals = [];
            foreach ($this->sectionsOriginal as $section) {
                $originals[] = static::addLineNumberPlaceholders($section, $lineLength);
            }

            $diff = new Diff2();
            $diff->setIgnoreStr('###LINENUMBER###');
            $diff->setFormatting($diffFormatting);
            $diffSections = $diff->compareSectionedHtml($originals, $this->sectionsNew);
            $htmlDiff     = implode("\n", $diffSections);

            $blocks = static::htmlDiff2LineBlocks($htmlDiff, $this->firstLine);
            $affectedBlocks = static::filterAffectedBlocks($blocks);
        } catch (Internal $e) {
            var_dump($e);
            die();
        }
        if ($grouped) {
            return static::groupAffectedDiffBlocks($affectedBlocks);
        } else {
            return $affectedBlocks;
        }
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
}
