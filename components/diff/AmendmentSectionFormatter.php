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

        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');
        $diff->setFormatting($this->diffFormatting);
        $diff->setDebug($this->debug);

        return $diff->computeDiff($strPre, $strPost);
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
        foreach ($blocks as $block) {
            $hadDiff = false;
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
            if ($inIns || $inDel || $hadDiff) {
                $affectedBlocks[] = $block;
            }
            if (preg_match('/<(ul|ol) class="inserted">.*<\/(ul|ol)>/siu', $block['text'])) {
                $affectedBlocks[] = $block;
            }
            if (preg_match('/<(ul|ol) class="deleted">.*<\/(ul|ol)>/siu', $block['text'])) {
                $affectedBlocks[] = $block;
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
        $lines = explode("\n", $computed);
        $out   = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (preg_match('/^<(ul|blockquote|ol)/siu', $line)) {
                $out[] = $line;
            } else {
                $line  = preg_replace('/<\/?p>/siu', '', $line);
                $parts = explode('###LINENUMBER###', $line);
                foreach ($parts as $j => $part) {
                    if ($part != '' || $j > 0) {
                        $out[] = '###LINENUMBER###' . $part;
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
    private function getDiffLinesWithNumbers()
    {
        $lineOffset = $this->section->getFirstLineNumber();
        $computed   = $this->getHtmlDiffWithLineNumberPlaceholders();
        $blocks     = static::htmlDiff2LineBlocks($computed, $lineOffset);
        return static::filterAffectedBlocks($blocks);
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
        $blocks     = static::htmlDiff2LineBlocks($computed, 1);
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
     * @return array
     * @throws Internal
     */
    public function getGroupedDiffLinesWithNumbers()
    {
        $diff = $this->getDiffLinesWithNumbers();
        return $diff;
        // @TODO

        $lastLine   = null;
        $blockBegin = null;
        $lines      = '';
        $blocks     = [];
        foreach ($diff as $lineNo => $str) {
            if ($lastLine === null || $lineNo > $lastLine + 2) {
                if ($blockBegin !== null) {
                    $blocks[] = [
                        'lineFrom' => $blockBegin,
                        'lineTo'   => $lastLine,
                        'text'     => $lines,
                    ];
                    $lines    = '';
                }
                $blockBegin = $lineNo;
            }
            $lines .= $str;
            $lastLine = $lineNo;
        }
        if ($lines != '') {
            $blocks[] = [
                'lineFrom' => $blockBegin,
                'lineTo'   => $lastLine,
                'text'     => $lines,
            ];
        }
        return $blocks;
    }
}
