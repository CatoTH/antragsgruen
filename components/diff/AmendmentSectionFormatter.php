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
    private function getHtmlDiffWithLineNumbers()
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

        return $diff->computeDiff($strPre, $strPost);

    }

    /**
     * @param array $computedLines
     * @param int $lineOffset
     * @param bool $prependLineNumber
     * @return array
     * @throws Internal
     */
    public static function getDiffLinesWithNumberComputed($computedLines, $lineOffset, $prependLineNumber)
    {
        $inIns         = $inDel = false;
        $affectedLines = [];
        for ($currLine = 0; $currLine < count($computedLines); $currLine++) {
            $hadDiff = false;
            if (preg_match_all('/<\/?(ins|del)>/siu', $computedLines[$currLine], $matches, PREG_OFFSET_CAPTURE)) {
                $hadDiff = true;
                foreach ($matches[0] as $found) {
                    switch ($found[0]) {
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
                $line = $computedLines[$currLine];
                $line = preg_replace('/<\/?(li|ul|ol|blockquote|p)>/siu', '', $line);
                if ($prependLineNumber) {
                    $line = '<span class="lineNumber" data-line-number="' .
                        ($currLine + $lineOffset) . '"></span>' . $line;
                }
                $affectedLines[$currLine] = $line;
            }

            if (preg_match('/<ul class="inserted">.*<\/ul>/siu', $computedLines[$currLine])) {
                $line = $computedLines[$currLine];
                if ($prependLineNumber) {
                    $line = '<span class="lineNumber" data-line-number="' .
                        ($currLine + $lineOffset) . '"></span>' . $line;
                }
                $affectedLines[$currLine] = $line;
            }
        }
        return $affectedLines;
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
     * @param bool $prependLineNumber
     * @return string[]
     * @throws Internal
     */
    private function getDiffLinesWithNumbers($prependLineNumber)
    {
        $lineOffset    = $this->section->getFirstLineNumber() - 1;
        $computed      = $this->getHtmlDiffWithLineNumbers();
        $computedLines = static::getDiffSplitToLines($computed);
        foreach ($computedLines as $i => $line) {
            $computedLines[$i] = str_replace('###LINENUMBER###', '', $line);
        }

        return static::getDiffLinesWithNumberComputed($computedLines, $lineOffset, $prependLineNumber);
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

        $computedLines = AmendmentSectionFormatter::getDiffSplitToLines($computed);
        foreach ($computedLines as $i => $line) {
            $computedLines[$i] = str_replace('###LINENUMBER###', '', $line);
        }
        $return = AmendmentSectionFormatter::getDiffLinesWithNumberComputed($computedLines, 1, true);

        return $return;
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
        $computed   = $this->getHtmlDiffWithLineNumbers();

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
    public function getInlineDiffGroupedLines()
    {
        $diff = $this->getDiffLinesWithNumbers(false);

        $lastLine   = null;
        $blockBegin = null;
        $lines      = "";
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


    /**
     * @return string
     * @throws Internal
     */
    public function getDiff()
    {
        if ($this->returnFullText) {
            return $this->getDiffFullText();
        } else {
            if ($this->returnInlineDiff) {
                return implode('<br>', $this->getDiffLinesWithNumbers(true));
            } else {
                throw new Internal('Not yet supported');
            }
        }
    }
}
