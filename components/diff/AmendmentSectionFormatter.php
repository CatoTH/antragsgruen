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
            foreach ($this->paragraphsOriginal as $section) {
                $originals[] = static::addLineNumberPlaceholders($section, $lineLength);
            }

            $diff = new Diff2();
            $diff->setIgnoreStr('###LINENUMBER###');
            $diffSections = $diff->compareSectionedHtml($originals, $this->paragraphsNew, $diffFormatting);
            $htmlDiff     = implode("\n", $diffSections);

            echo $htmlDiff . "\n\n";

            $affectedBlocks = AffectedLinesFilter::splitToAffectedLines($htmlDiff, $this->firstLine);
        } catch (Internal $e) {
            var_dump($e);
            die();
        }
        return $affectedBlocks;
    }

}
