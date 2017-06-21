<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\exceptions\Internal;
use yii\helpers\Html;

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
     * @param string $paragraphs
     * @return array
     */
    public static function extractInsDelBlocks($paragraphs)
    {
        $detectInsDel = function ($str) {
            if (stripos($str, '<ins') !== false || stripos($str, '</ins') !== false) {
                return true;
            }
            if (stripos($str, '<del') !== false || stripos($str, '</del') !== false) {
                return true;
            }
            return false;
        };

        $blocks = ['ins' => [], 'del' => []];
        do {
            $pre        = $paragraphs;
            $paragraphs = preg_replace_callback(
                '/<(?<tag>p|div|blockquote)>(<del>(?<del>.*)<\/del>)?(<ins>(?<ins>.*)<\/ins>)?<\/\1>/siu',
                function ($matches) use (&$blocks, $detectInsDel) {
                    $tag = $matches['tag'];
                    if (isset($matches['ins'])) {
                        if ($detectInsDel($matches['ins'])) {
                            return $matches[0];
                        }
                        $blocks['ins'][] = '<' . $tag . ' class="inserted">' . $matches['ins'] . '</' . $tag . '>';
                    }
                    if (isset($matches['del'])) {
                        if ($detectInsDel($matches['del'])) {
                            return $matches[0];
                        }
                        $blocks['del'][] = '<' . $tag . ' class="deleted">' . $matches['del'] . '</' . $tag . '>';
                    }
                    return '';
                },
                $paragraphs
            );
            $paragraphs = preg_replace_callback(
                '/<(?<tag>p|ul|ol|div|blockquote) class=["\']?inserted["\']?>(.*)<\/\1>/siu',
                function ($matches) use (&$blocks) {
                    $blocks['ins'][] = $matches[0];
                    return '';
                },
                $paragraphs
            );
            $paragraphs = preg_replace_callback(
                '/<(?<tag>p|ul|ol|div|blockquote) class=["\']?deleted["\']?>(.*)<\/\1>/siu',
                function ($matches) use (&$blocks) {
                    $blocks['del'][] = $matches[0];
                    return '';
                },
                $paragraphs
            );
        } while ($pre != $paragraphs);

        if (trim($paragraphs) != '') {
            // Something remains => it's not a pure replacement
            return null;
        } else {
            return $blocks;
        }
    }

    /**
     * @param array $diffSections
     * @return array
     */
    public static function groupConsecutiveChangeBlocks($diffSections)
    {
        $pendingBlocks = null;
        $blocksOut     = [];
        foreach ($diffSections as $diffSection) {
            if ($blocks = static::extractInsDelBlocks($diffSection)) {
                if ($pendingBlocks) {
                    $pendingBlocks['ins'] = array_merge($pendingBlocks['ins'], $blocks['ins']);
                    $pendingBlocks['del'] = array_merge($pendingBlocks['del'], $blocks['del']);
                } else {
                    $pendingBlocks = $blocks;
                }
            } else {
                if ($pendingBlocks) {
                    $blocksOut     = array_merge($blocksOut, $pendingBlocks['del']);
                    $blocksOut     = array_merge($blocksOut, $pendingBlocks['ins']);
                    $pendingBlocks = null;
                }
                $blocksOut[] = $diffSection;
            }
        }
        if ($pendingBlocks) {
            $blocksOut = array_merge($blocksOut, $pendingBlocks['del']);
            $blocksOut = array_merge($blocksOut, $pendingBlocks['ins']);
        }
        return $blocksOut;
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
            $originals     = [];
            $newParagraphs = [];
            foreach ($this->paragraphsOriginal as $section) {
                $originals[] = static::addLineNumberPlaceholders($section, $lineLength);
            }
            foreach ($this->paragraphsNew as $newParagraph) {
                // Besides adding line numbers, addLineNumberPlaceholders also breaks overly long words into parts
                // and addes a dash at the end of the first line. We need to do this on the amendments as well,
                // even if we don't need the line number markers
                $newParagraph    = static::addLineNumberPlaceholders($newParagraph, $lineLength);
                $newParagraph    = str_replace('###LINENUMBER###', '', $newParagraph);
                $newParagraphs[] = $newParagraph;
            }

            $diff         = new Diff();
            $diffSections = $diff->compareHtmlParagraphs($originals, $newParagraphs, $diffFormatting);
            $diffSections = static::groupConsecutiveChangeBlocks($diffSections);
            $htmlDiff     = implode("\n", $diffSections);

            $affectedBlocks = AffectedLinesFilter::splitToAffectedLines($htmlDiff, $this->firstLine);
        } catch (Internal $e) {
            var_dump($e);
            die();
        }
        return $affectedBlocks;
    }
}
