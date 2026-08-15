<?php

declare(strict_types=1);

namespace app\components\diff;

use app\components\diff\DataTypes\DiffWord;
use app\models\exceptions\Internal;

/**
 * Combines two diffs against the *same* original text into a single, two-layered diff.
 *
 * This is what makes an amendment amending another amendment readable: the changes of the amendment being amended
 * (the "parent") form the outer layer, the changes the amending amendment (the "child") makes relative to that parent
 * form the inner layer. Given
 *
 *   original: Test 123 aber das hier nicht
 *   parent:   Test 123<ins>4567</ins> aber das hier <del>nicht</del>
 *   child:    Test 123<ins>4568</ins> aber das hier <del>nich</del>t
 *
 * the combined result reads (schematically)
 *
 *   Test 123<ins class="outer">456<del>7</del><ins>8</ins></ins> aber das hier <del class="outer">nicht</del><ins>t</ins>
 *
 * Both layers are anchored on the *original motion text*, not on the parent amendment's text. That is essential:
 * the ###LINENUMBER### markers live in the original text, so anchoring anywhere else would break line numbering
 * (and with it AffectedLinesFilter). It also makes the combination step trivial: two diffs against the same original,
 * run through Diff::compareHtmlParagraphsToWordArray(), are token-aligned by construction
 * (guaranteed by Diff::checkWordArrayConsistency()), so they can simply be walked in lockstep.
 *
 * The outer layer is deliberately whatever the parent amendment's own view shows — the same Diff engine, the same
 * heuristics. Where the engine renders a word change as a deletion plus an insertion rather than as a character-level
 * change, the outer layer does so as well, so both views stay consistent with each other.
 *
 * The output uses the regular ###INS_START###-style markers for the inner layer and the ###OUTER_INS_START###-style
 * markers for the outer one, and is rendered by DiffRenderer::renderTwoLayerHtmlWithPlaceholders().
 * Inner markers never cross the boundary of an outer marker; see emit() below.
 */
class TwoLayerDiff
{
    private const STATE_NONE = 0;
    private const STATE_INS  = 1;
    private const STATE_DEL  = 2;

    private const MARKER_SPLIT = '/(###(?:INS|DEL)_(?:START|END)[^#]{0,20}###)/siu';
    private const MARKER_MATCH = '/^###(?<type>INS|DEL)_(?<pos>START|END)[^#]{0,20}###$/siu';

    private Diff $diff;

    private string $out = '';
    private int $openOuter = self::STATE_NONE;
    private int $openInner = self::STATE_NONE;

    public function __construct()
    {
        $this->diff = new Diff();
        $this->diff->setIgnoreStr('###LINENUMBER###');
    }

    /**
     * Returns one string with diff markers per paragraph of the original text, or null if no consistent
     * two-layered result could be built. In the latter case the caller is expected to fall back to
     * showing the two amendments' diffs separately.
     *
     * @param string[] $originalParas paragraphs of the original motion text, including ###LINENUMBER### markers
     * @param string[] $parentParas   paragraphs of the amendment that is being amended
     * @param string[] $childParas    paragraphs of the amendment amending it
     * @return string[]|null
     */
    public function computeParagraphs(array $originalParas, array $parentParas, array $childParas): ?array
    {
        try {
            $parentWords = $this->diff->compareHtmlParagraphsToWordArray($originalParas, $parentParas);
            $childWords  = $this->diff->compareHtmlParagraphsToWordArray($originalParas, $childParas);
        } catch (Internal $e) {
            return null;
        }

        if (count($parentWords) !== count($childWords)) {
            return null;
        }

        $combinedParas = [];
        foreach ($parentWords as $paraNo => $parentWordArr) {
            $childWordArr = $childWords[$paraNo];

            $original = self::wordArrayToOriginal($parentWordArr);
            if ($original !== self::wordArrayToOriginal($childWordArr)) {
                return null;
            }

            $combined = $this->combineParagraph(
                $original,
                self::wordArrayToDiff($parentWordArr),
                self::wordArrayToDiff($childWordArr)
            );
            if ($combined === null) {
                return null;
            }
            $combinedParas[] = $combined;
        }

        return $combinedParas;
    }

    /**
     * @param string[] $originalParas
     * @param string[] $parentParas
     * @param string[] $childParas
     * @return string[]|null one rendered HTML string per paragraph of the original text
     */
    public function computeAndRenderParagraphs(array $originalParas, array $parentParas, array $childParas, int $diffFormatting): ?array
    {
        $combinedParas = $this->computeParagraphs($originalParas, $parentParas, $childParas);
        if ($combinedParas === null) {
            return null;
        }

        $rendered = [];
        foreach ($combinedParas as $combinedPara) {
            $html = DiffRenderer::renderTwoLayerHtmlWithPlaceholders($combinedPara, $diffFormatting);

            // A line number marker that is the only content of a deletion would otherwise shift all following lines
            $html = preg_replace('/<del( [^>]*)?>###LINENUMBER###<\/del>/siu', '###LINENUMBER###', $html);

            $rendered[] = $html;
        }

        return $rendered;
    }

    /**
     * @param DiffWord[] $wordArr
     */
    private static function wordArrayToOriginal(array $wordArr): string
    {
        return implode('', array_map(fn (DiffWord $word) => $word->word, $wordArr));
    }

    /**
     * @param DiffWord[] $wordArr
     */
    private static function wordArrayToDiff(array $wordArr): string
    {
        return implode('', array_map(fn (DiffWord $word) => $word->diff, $wordArr));
    }

    /**
     * Decomposes a diff into the two things that can be combined across amendments:
     * - which parts of the original are kept and which are deleted ("spans", tiling the whole original)
     * - what is inserted at which position of the original ("inserts")
     *
     * Positions are byte offsets into $original. All boundaries are character boundaries in both diffs,
     * as the diff is always assembled from whole characters, so plain substr() is safe (and much faster
     * than the grapheme_* functions).
     *
     * @return array{spans: list<array{0: int, 1: int, 2: bool}>, inserts: array<int, string>}|null
     */
    private static function parseDiff(string $original, string $diff): ?array
    {
        $parts = preg_split(self::MARKER_SPLIT, $diff, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            return null;
        }

        $state   = self::STATE_NONE;
        $offset  = 0;
        $spans   = [];
        $inserts = [];

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (preg_match(self::MARKER_MATCH, $part, $matches)) {
                $type = ($matches['type'] === 'INS' ? self::STATE_INS : self::STATE_DEL);
                if ($matches['pos'] === 'START') {
                    if ($state !== self::STATE_NONE) {
                        return null; // Nested or unbalanced markers are not supported
                    }
                    $state = $type;
                } else {
                    if ($state !== $type) {
                        return null;
                    }
                    $state = self::STATE_NONE;
                }
                continue;
            }

            if ($state === self::STATE_INS) {
                $inserts[$offset] = ($inserts[$offset] ?? '') . $part;
            } else {
                $len     = strlen($part);
                $spans[] = [$offset, $offset + $len, $state === self::STATE_DEL];
                if (substr($original, $offset, $len) !== $part) {
                    // The unchanged and deleted parts of a diff have to add up to the original text again
                    return null;
                }
                $offset += $len;
            }
        }

        if ($state !== self::STATE_NONE || $offset !== strlen($original)) {
            return null;
        }

        return ['spans' => $spans, 'inserts' => $inserts];
    }

    /**
     * @param list<array{0: int, 1: int, 2: bool}> $spans
     */
    private static function isDeletedAt(array $spans, int $offset): bool
    {
        foreach ($spans as $span) {
            if ($offset >= $span[0] && $offset < $span[1]) {
                return $span[2];
            }
        }
        return false;
    }

    private function combineParagraph(string $original, string $parentDiff, string $childDiff): ?string
    {
        $parent = self::parseDiff($original, $parentDiff);
        $child  = self::parseDiff($original, $childDiff);
        if ($parent === null || $child === null) {
            return null;
        }

        $boundaries = [0, strlen($original)];
        foreach ([$parent, $child] as $parsed) {
            foreach ($parsed['spans'] as $span) {
                $boundaries[] = $span[0];
                $boundaries[] = $span[1];
            }
            foreach (array_keys($parsed['inserts']) as $insertOffset) {
                $boundaries[] = $insertOffset;
            }
        }
        $boundaries = array_unique($boundaries);
        sort($boundaries);

        $this->out       = '';
        $this->openOuter = self::STATE_NONE;
        $this->openInner = self::STATE_NONE;

        for ($i = 0; $i < count($boundaries); $i++) {
            $from = $boundaries[$i];

            // Insertions are anchored *after* the text preceding them
            $this->emitInsertion($parent['inserts'][$from] ?? '', $child['inserts'][$from] ?? '');

            if ($i + 1 < count($boundaries)) {
                $to = $boundaries[$i + 1];
                $this->emitOriginalText(
                    substr($original, $from, $to - $from),
                    self::isDeletedAt($parent['spans'], $from),
                    self::isDeletedAt($child['spans'], $from)
                );
            }
        }

        $this->closeInner();
        $this->closeOuter();

        return $this->out;
    }

    /**
     * A piece of the original text, given whether the parent and/or the child amendment deletes it.
     */
    private function emitOriginalText(string $text, bool $deletedByParent, bool $deletedByChild): void
    {
        if (!$deletedByParent && !$deletedByChild) {
            $this->emit(self::STATE_NONE, self::STATE_NONE, $text);
        } elseif ($deletedByParent && $deletedByChild) {
            // Both want it gone: nothing changed between the two amendments
            $this->emit(self::STATE_DEL, self::STATE_NONE, $text);
        } elseif ($deletedByChild) {
            // A deletion newly introduced by the child amendment
            $this->emit(self::STATE_NONE, self::STATE_DEL, $text);
        } else {
            // The child amendment keeps what the parent amendment wanted to delete: an un-deletion
            $this->emit(self::STATE_DEL, self::STATE_INS, $text);
        }
    }

    /**
     * What the parent and the child amendment insert at one and the same position of the original text.
     */
    private function emitInsertion(string $parentInsert, string $childInsert): void
    {
        if ($parentInsert === '' && $childInsert === '') {
            return;
        }

        if ($childInsert === '') {
            // The child amendment drops what the parent amendment inserted
            $this->emit(self::STATE_INS, self::STATE_DEL, $parentInsert);
        } elseif ($parentInsert === '') {
            // Text the child amendment adds where the parent amendment added nothing
            $this->emit(self::STATE_NONE, self::STATE_INS, $childInsert);
        } elseif ($parentInsert === $childInsert) {
            $this->emit(self::STATE_INS, self::STATE_NONE, $parentInsert);
        } else {
            // Both insert something here: the whole slot belongs to the parent's insertion,
            // and how the child rewrites it is diffed within it.
            $this->emitRaw(self::STATE_INS, $this->diff->computeLineDiff($parentInsert, $childInsert));
        }
    }

    private function emit(int $outer, int $inner, string $text): void
    {
        if ($text === '') {
            return;
        }
        $this->switchTo($outer, $inner);
        $this->out .= $text;
    }

    /**
     * Appends text that already carries its own inner markers.
     */
    private function emitRaw(int $outer, string $text): void
    {
        if ($text === '') {
            return;
        }
        $this->switchTo($outer, self::STATE_NONE);
        $this->out .= $text;
    }

    private function switchTo(int $outer, int $inner): void
    {
        if ($outer !== $this->openOuter) {
            // Inner markers must never cross the boundary of an outer one: DiffRenderer renders the two layers
            // in two passes and can only do so if the result is properly nested.
            $this->closeInner();
            $this->closeOuter();

            if ($outer === self::STATE_INS) {
                $this->out .= DiffRenderer::OUTER_INS_START;
            } elseif ($outer === self::STATE_DEL) {
                $this->out .= DiffRenderer::OUTER_DEL_START;
            }
            $this->openOuter = $outer;
        }

        if ($inner !== $this->openInner) {
            $this->closeInner();

            if ($inner === self::STATE_INS) {
                $this->out .= DiffRenderer::INS_START;
            } elseif ($inner === self::STATE_DEL) {
                $this->out .= DiffRenderer::DEL_START;
            }
            $this->openInner = $inner;
        }
    }

    private function closeInner(): void
    {
        if ($this->openInner === self::STATE_INS) {
            $this->out .= DiffRenderer::INS_END;
        } elseif ($this->openInner === self::STATE_DEL) {
            $this->out .= DiffRenderer::DEL_END;
        }
        $this->openInner = self::STATE_NONE;
    }

    private function closeOuter(): void
    {
        if ($this->openOuter === self::STATE_INS) {
            $this->out .= DiffRenderer::OUTER_INS_END;
        } elseif ($this->openOuter === self::STATE_DEL) {
            $this->out .= DiffRenderer::OUTER_DEL_END;
        }
        $this->openOuter = self::STATE_NONE;
    }
}
