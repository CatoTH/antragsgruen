<?php
namespace app\components\diff;


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


    /**
     * @param AmendmentSection $amendmentSection
     */
    public function __construct(AmendmentSection $amendmentSection)
    {
        $this->section = $amendmentSection;
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
        $diff = new Diff();
        $diff->setIgnoreStr('###LINENUMBER###');

        $strPost = implode("\n", $this->section->getTextParagraphs()) . "\n";

        return $diff->computeDiff($strPre, $strPost);
    }


    /**
     * @param bool $prependLineNumber
     * @return string[]
     * @throws Internal
     */
    private function getDiffLinesWithNumbers($prependLineNumber)
    {
        $lineOffset    = $this->section->getFirstLineNo() - 1;
        $computed      = $this->getHtmlDiffWithLineNumbers();
        $computedLines = explode('###LINENUMBER###', $computed);

        $inIns         = $inDel = false;
        $affectedLines = [];
        for ($currLine = 1; $currLine < count($computedLines); $currLine++) {
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
        }
        return $affectedLines;
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
        $lineOffset = $this->section->getFirstLineNo() - 1;
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
        $lines = "";
        $blocks     = [];
        foreach ($diff as $lineNo => $str) {
            if ($lastLine === null || $lineNo > $lastLine + 2) {
                if ($blockBegin !== null) {
                    $blocks[] = [
                        'lineFrom' => $blockBegin,
                        'lineTo' => $lastLine,
                        'text' => $lines,
                    ];
                    $lines = '';
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
