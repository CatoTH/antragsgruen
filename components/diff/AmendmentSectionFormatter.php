<?php
namespace app\components\diff;

use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\db\AmendmentSection;
use app\models\exceptions\Internal;
use app\models\sectionTypes\ISectionType;

class AmendmentSectionFormatter
{
    /** @var AmendmentSection */
    private $section;

    /** @var bool */
    private $returnFullText   = true;
    private $returnInlineDiff = true;

    /** @var int */
    private $diffFormatting = 0;

    /** @var bool */
    private $debug = false;


    /**
     * @param AmendmentSection $amendmentSection
     * @param int $diffFormatting
     */
    public function __construct(AmendmentSection $amendmentSection, $diffFormatting)
    {
        $this->section        = $amendmentSection;
        $this->diffFormatting = $diffFormatting;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param $set
     */
    public function setReturnFullText($set)
    {
        $this->returnFullText = $set;
    }

    /**
     * @param $set
     */
    public function setReturnInlineDiff($set)
    {
        $this->returnInlineDiff = $set;
    }

    /**
     * @return string
     * @throws Internal
     */
    private function getHtmlDiffWithLineNumberPlaceholders()
    {
        if ($this->section->consultationSetting->type != ISectionType::TYPE_TEXT_SIMPLE) {
            throw new Internal('Only supported for simple HTML');
        }
        $strPre = null;
        foreach ($this->section->amendment->motion->sections as $section) {
            if ($section->sectionId == $this->section->sectionId) {
                $strPre = $section->getTextWithLineNumberPlaceholders();
            }
        }
        if ($strPre === null) {
            throw new Internal('Original version not found');
        }

        $lineLength = $this->section->consultationSetting->motionType->consultation->getSettings()->lineLength;
        $strPost    = '';
        foreach ($this->section->getTextParagraphs() as $para) {
            $linesOut = LineSplitter::motionPara2lines($para, false, $lineLength);
            $strPost .= implode(' ', $linesOut) . "\n";
        }
        $strPost = trim($strPost);

        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $diff->setFormatting($this->diffFormatting);
        $diff->setDebug($this->debug);

        $return = $diff->computeDiff($strPre, $strPost);
        return $diff->cleanupDiffProblems($return);
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
        $inIns          = $inDel = false;
        $affectedBlocks = [];
        $interveningCount = 0;
        $lastBlock = null;
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
            $addBlock = function () use ($block,&$affectedBlocks,$lastBlock,&$interveningCount) {
                if ($interveningCount == 2)
                    $affectedBlocks[] = $lastBlock;
                $affectedBlocks[] = $block;
                $interveningCount = 0;
            };
            if ($inIns) {
                $block['text']    = $block['text'] . '</ins>';
                $addBlock ();
            } elseif ($inDel) {
                $block['text']    = $block['text'] . '</del>';
                $addBlock ();
            } elseif ($hadDiff) {
                $addBlock ();
            } else {
                if (preg_match('/<(ul|ol) class="inserted">.*<\/(ul|ol)>/siu', $block['text'])) {
                    $addBlock ();
                }
                if (preg_match('/<(ul|ol) class="deleted">.*<\/(ul|ol)>/siu', $block['text'])) {
                    $addBlock ();
                }
            }
            $interveningCount++;
            $lastBlock = $block;
        }
        return $affectedBlocks;
    }

    /**
     * @param string $computed
     * @return array
     */
    public static function getDiffSplitToLines($computed)
    {
        $lines = explode("\n", $computed);

        for ($i = 0; $i < count($lines) - 1; $i++) {
            $last5 = mb_substr($lines[$i], mb_strlen($lines[$i]) - 5);
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

        $out   = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (preg_match('/^<(ul|blockquote|ol)/siu', $line)) {
                $out[] = $line;
            } else {
                $line          = preg_replace('/<\/?p>/siu', '', $line);
                $hasLineNumber = (mb_strpos($line, '###LINENUMBER###') !== false);
                $parts         = explode('###LINENUMBER###', $line);
                $dangling      = '';
                foreach ($parts as $j => $part) {
                    if ($part != '' || $j > 0) {
                        if ($part == '<ins>' || $part == '<del>') {
                            $dangling = $part;
                        } else {
                            if ($hasLineNumber) {
                                $part = '###LINENUMBER###' . $part;
                            }
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
        $computedLines = static::getDiffSplitToLines($htmlDiff);
        $lineNo        = 0;
        $blocks        = [];
        foreach ($computedLines as $line) {
            $substrcount            = mb_substr_count($line, '###LINENUMBER###');
            $computedLines[$lineNo] = str_replace('###LINENUMBER###', '', $line);
            if ($substrcount == 0) {
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
     * @return array[]
     * @throws Internal
     */
    public function getDiffLinesWithNumbers()
    {
        if (!$this->section) {
            return [];
        }
        $getDiffLinesWithNumbers = $this->section->getCacheItem('getDiffLinesWithNumbers');
        if ($getDiffLinesWithNumbers === null) {
            try {
                $lineOffset              = $this->section->getFirstLineNumber();
                $computed                = $this->getHtmlDiffWithLineNumberPlaceholders();
                $blocks                  = static::htmlDiff2LineBlocks($computed, $lineOffset);
                $getDiffLinesWithNumbers = static::filterAffectedBlocks($blocks);
            } catch (Internal $e) {
                $getDiffLinesWithNumbers = [];
            }
            $this->section->setCacheItem('getDiffLinesWithNumbers', $getDiffLinesWithNumbers);
        }
        return $getDiffLinesWithNumbers;
    }

    /**
     * Used by unit tests; should resemble the process above as closely as possible
     * @param string $textPre
     * @param string $textPost
     * @return array
     */
    public static function getDiffLinesWithNumbersDebug($textPre, $textPost)
    {
        $origLines = HTMLTools::sectionSimpleHTML($textPre);
        $strPre    = '';
        foreach ($origLines as $para) {
            $linesOut = LineSplitter::motionPara2lines($para, true, 80);
            $strPre .= implode(' ', $linesOut) . "\n";
        }

        $newLines = HTMLTools::sectionSimpleHTML($textPost);
        $strPost  = '';
        foreach ($newLines as $para) {
            $linesOut = LineSplitter::motionPara2lines($para, false, 80);
            $strPost .= implode(' ', $linesOut) . "\n";
        }

        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $diff->setFormatting(Diff::FORMATTING_CLASSES);
        $computed = $diff->computeDiff($strPre, $strPost);
        $blocks   = static::htmlDiff2LineBlocks($computed, 1);
        return static::filterAffectedBlocks($blocks);
    }

    /**
     * @return string
     * @throws Internal
     */
    private function getDiffFullText()
    {
        if (!$this->returnInlineDiff) {
            throw new Internal('Invalid combination of settings');
        }
        $lineOffset = $this->section->getFirstLineNumber() - 1;
        $computed   = $this->getHtmlDiffWithLineNumberPlaceholders();

        $computedLines = explode('###LINENUMBER###', $computed);
        $out           = $computedLines[0];
        for ($currLine = 1; $currLine < count($computedLines); $currLine++) {
            $out .= '<span class="lineNumber" data-line-number="' . ($currLine + $lineOffset) . '"></span>';
            $out .= $computedLines[$currLine];
            $out .= '<br>';
        }
        $out = str_replace('<li><br>', '<li>', $out);
        $out = str_replace('<blockquote><br>', '<blockquote>', $out);
        return $out;
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
            if ($currBlock === null || $block['lineFrom'] > $currBlock['lineTo'] + 2) {
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
                $currBlock['text'] .= '<br>';
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
     * @return array
     * @throws Internal
     */
    public function getGroupedDiffLinesWithNumbers()
    {
        $blocks = $this->getDiffLinesWithNumbers();
        return static::groupAffectedDiffBlocks($blocks);
    }
}
