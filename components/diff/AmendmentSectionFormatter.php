<?php
namespace app\components\diff;

use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\exceptions\Internal;

class AmendmentSectionFormatter
{
    /** @var string[] */
    private $paragraphsOriginal;
    private $paragraphsNew;

    /** @var int */
    private $firstLine = 0;

    /** @var bool */
    private $debug = false;

    /**
     * @param string $text
     */
    public function setTextOriginal($text)
    {
        $this->paragraphsOriginal = HTMLTools::sectionSimpleHTML($text);
    }

    /**
     * @param string $text
     */
    public function setTextNew($text)
    {
        $this->paragraphsNew = HTMLTools::sectionSimpleHTML($text);
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
        $linesOut = LineSplitter::splitHtmlToLines($text, $lineLength, '###LINENUMBER###');
        return implode('', $linesOut);
    }



    /**
     * @param int $lineLength
     * @param int $diffFormatting
     * @return array[]
     * @throws Internal
     */
    public function getDiffGroupsWithNumbers($lineLength, $diffFormatting)
    {
        try {
            $originals = [];
            $newParagraphs = [];
            foreach ($this->paragraphsOriginal as $section) {
                $originals[] = static::addLineNumberPlaceholders($section, $lineLength);
            }
            foreach ($this->paragraphsNew as $newParagraph) {
                // Besides adding line numbers, addLineNumberPlaceholders also breaks overly long words into parts
                // and addes a dash at the end of the first line. We need to do this on the amendments as well,
                // even if we don't need the line number markers
                $newParagraph = static::addLineNumberPlaceholders($newParagraph, $lineLength);
                $newParagraph = str_replace('###LINENUMBER###', '', $newParagraph);
                $newParagraphs[] = $newParagraph;
            }

            $diff = new Diff2();
            $diffSections = $diff->compareHtmlParagraphs($originals, $newParagraphs, $diffFormatting);
            $htmlDiff     = implode("\n", $diffSections);

            $affectedBlocks = AffectedLinesFilter::splitToAffectedLines($htmlDiff, $this->firstLine);
        } catch (Internal $e) {
            var_dump($e);
            die();
        }
        return $affectedBlocks;
    }

}
