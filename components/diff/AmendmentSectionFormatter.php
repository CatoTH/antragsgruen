<?php

namespace app\components\diff;

use app\components\diff\DataTypes\AffectedLineBlock;
use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\exceptions\Internal;
use app\models\SectionedParagraph;

class AmendmentSectionFormatter
{
    /** @var SectionedParagraph[] */
    private array $paragraphsOriginal;
    /** @var SectionedParagraph[] */
    private array $paragraphsNew;
    /** @var SectionedParagraph[]|null */
    private ?array $paragraphsParent = null;

    private int $firstLine = 0;
    /** @phpstan-ignore-next-line */
    private bool $debug = false;

    public function setTextOriginal(string $text): void
    {
        $this->paragraphsOriginal = HTMLTools::sectionSimpleHTML($text);
    }

    public function setTextNew(string $text): void
    {
        $this->paragraphsNew = HTMLTools::sectionSimpleHTML($text);
    }

    /**
     * The text of the amendment that the amendment set by setTextNew() amends.
     * Only relevant for the two-layered diff, see getTwoLayerDiff*().
     */
    public function setTextParent(string $text): void
    {
        $this->paragraphsParent = HTMLTools::sectionSimpleHTML($text);
    }

    public function setFirstLineNo(int $lineNo): void
    {
        $this->firstLine = $lineNo;
    }

    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    public static function addLineNumberPlaceholders(string $text, int $lineLength): string
    {
        $linesOut = LineSplitter::splitHtmlToLines($text, $lineLength, '###LINENUMBER###');
        return implode('', $linesOut);
    }

    public static function extractInsDelBlocks(string $paragraphs): ?array
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

        if (trim($paragraphs) !== '') {
            // Something remains => it's not a pure replacement
            return null;
        } else {
            return $blocks;
        }
    }

    public static function groupConsecutiveChangeBlocks(array $diffSections): array
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
     * @param SectionedParagraph[] $paragraphs
     * @return SectionedParagraph[]
     */
    private function prepareOriginalParagraphs(array $paragraphs, int $lineLength): array
    {
        $prepared = [];
        foreach ($paragraphs as $section) {
            $section = clone $section;
            $section->html = static::addLineNumberPlaceholders($section->html, $lineLength);
            $section->html = HTMLTools::explicitlySetLiValues($section->html);
            $prepared[] = $section;
        }
        return $prepared;
    }

    /**
     * @param SectionedParagraph[] $paragraphs
     * @return SectionedParagraph[]
     */
    private function prepareAmendedParagraphs(array $paragraphs, int $lineLength): array
    {
        $prepared = [];
        foreach ($paragraphs as $newParagraph) {
            // Besides adding line numbers, addLineNumberPlaceholders also breaks overly long words into parts
            // and addes a dash at the end of the first line. We need to do this on the amendments as well,
            // even if we don't need the line number markers
            $newParagraph = clone $newParagraph;
            $newParagraph->html = static::addLineNumberPlaceholders($newParagraph->html, $lineLength);
            $newParagraph->html = str_replace('###LINENUMBER###', '', $newParagraph->html);
            $prepared[] = $newParagraph;
        }
        return $prepared;
    }

    public function getDiffSectionsWithNumbers(int $lineLength, int $diffFormatting): array
    {
        try {
            $originals     = $this->prepareOriginalParagraphs($this->paragraphsOriginal, $lineLength);
            $newParagraphs = $this->prepareAmendedParagraphs($this->paragraphsNew, $lineLength);

            $diff         = new Diff();
            return $diff->compareHtmlParagraphs($originals, $newParagraphs, $diffFormatting);
        } catch (Internal $e) {
            var_dump($e);
            die();
        }
    }

    /**
     * The diff of this amendment against the original text, with the changes of the amendment it amends
     * (set via setTextParent()) shown as an additional, outer layer. See TwoLayerDiff.
     *
     * Returns null if no consistent two-layered diff could be built; the caller is then expected to fall back
     * to showing the two amendments' diffs separately.
     *
     * @return string[]|null
     */
    public function getTwoLayerDiffSectionsWithNumbers(int $lineLength, int $diffFormatting): ?array
    {
        if ($this->paragraphsParent === null) {
            return null;
        }

        $toHtml = fn (SectionedParagraph $para) => $para->html;

        return (new TwoLayerDiff())->computeAndRenderParagraphs(
            array_map($toHtml, $this->prepareOriginalParagraphs($this->paragraphsOriginal, $lineLength)),
            array_map($toHtml, $this->prepareAmendedParagraphs($this->paragraphsParent, $lineLength)),
            array_map($toHtml, $this->prepareAmendedParagraphs($this->paragraphsNew, $lineLength)),
            $diffFormatting
        );
    }

    /**
     * @return AffectedLineBlock[]|null
     */
    public function getTwoLayerDiffGroupsWithNumbers(int $lineLength, int $diffFormatting, ?int $context = null): ?array
    {
        $diffSections = $this->getTwoLayerDiffSectionsWithNumbers($lineLength, $diffFormatting);
        if ($diffSections === null) {
            return null;
        }

        $diffSections = static::groupConsecutiveChangeBlocks($diffSections);

        return AffectedLinesFilter::splitToAffectedLines(implode("\n", $diffSections), $this->firstLine, $context ?? 1);
    }

    /**
     * @return AffectedLineBlock[]
     */
    public function getDiffGroupsWithNumbers(int $lineLength, int $diffFormatting, ?int $context = null): array
    {
        if ($context === null) {
            $context = 1;
        }
        try {
            $diffSections = $this->getDiffSectionsWithNumbers($lineLength, $diffFormatting);
            $diffSections = static::groupConsecutiveChangeBlocks($diffSections);
            $htmlDiff     = implode("\n", $diffSections);

            $affectedBlocks = AffectedLinesFilter::splitToAffectedLines($htmlDiff, $this->firstLine, $context);
        } catch (Internal $e) {
            var_dump($e);
            die();
        }
        return $affectedBlocks;
    }
}
