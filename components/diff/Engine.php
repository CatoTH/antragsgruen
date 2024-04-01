<?php /** @noinspection PhpMissingReturnTypeInspection */

namespace app\components\diff;

use app\components\diff\DataTypes\InsDelGroup;

/*
 * Hint: type declarations are missing on purpose in this class, as they unfortunately slow down PHP.
 */

/*
http://code.stephenmorley.org/php/diff-implementation/

class.Diff.php

A class containing a diff implementation

Created by Stephen Morley - http://stephenmorley.org/ - and released under the
terms of the CC0 1.0 Universal legal code:

http://creativecommons.org/publicdomain/zero/1.0/legalcode
*/

class Engine
{
    // define the constants
    const UNMODIFIED = 0;
    const DELETED    = 1;
    const INSERTED   = 2;

    private string $IGNORE_STR = '';


    public function setIgnoreStr(string $str): void
    {
        $this->IGNORE_STR = $str;
    }

    public function getIgnoreStr(): string
    {
        return $this->IGNORE_STR;
    }

    private function strCmp(string $str1, string $str2, bool $relaxedTags): bool
    {
        if ($this->IGNORE_STR !== '') {
            $str1 = str_replace($this->IGNORE_STR, '', $str1);
            $str2 = str_replace($this->IGNORE_STR, '', $str2);
        }

        if ($relaxedTags) {
            $str1 = preg_replace('/<li[^>]*>/siu', '<li>', $str1);
            $str2 = preg_replace('/<li[^>]*>/siu', '<li>', $str2);
        }

        // Ignoring some changes in pure HTML tags
        if ($str1 !== '' && $str1[0] === '<' && $str2 !== '' && $str2[0] === '<' && preg_match('/^<[^>]+>$/', $str1) && preg_match('/^<[^>]+>$/', $str2)) {
            // Changing attributes of list items is not supported by the diff, as this would get too messy (ol start=2 => ol start=3)
            if (stripos($str1, '<ol') === 0 && stripos($str2, '<ol') === 0) {
                return true;
            }
            if (stripos($str1, '<ul') === 0 && stripos($str2, '<ul') === 0) {
                return true;
            }
            if (stripos($str1, '<li') === 0 && stripos($str2, '<li') === 0) {
                return true;
            }
        }

        return ($str1 === $str2);
    }

    /**
     * This gives the offsets of the first string in the two arrays that is not equal.
     * As we might be ignoring a string (###LINENUMBER###), that might not be the same index for both array
     *
     * @return int[]
     */
    private function getArrayDiffStarts(array $strings1, array $strings2, bool $relaxedTags): array
    {
        $start1 = 0;
        $start2 = 0;
        $end1   = count($strings1) - 1;
        $end2   = count($strings2) - 1;

        loop:
        if ($start1 > $end1 || $start2 > $end2) {
            return [$start1, $start2];
        }

        if ($strings1[$start1] === $this->IGNORE_STR && $strings2[$start2] !== $this->IGNORE_STR &&
            ($start1 + 1) <= $end1 && $strings1[$start1 + 1] === $strings2[$start2]) {
            $start1++;
        }

        if (!$this->strCmp($strings1[$start1], $strings2[$start2], $relaxedTags)) {
            return [$start1, $start2];
        }
        $start1++;
        $start2++;
        goto loop; // This feels so wrong, yet so right...

        return []; // Fake return for phpstan
    }

    /**
     * This returns the last index of the two string arrays that are different -
     * taking into account that some string might be ignored (###LINENUMBER###)
     * and that the end indexes should not be before the start indexes
     *
     * @return int[]
     */
    private function getArrayDiffEnds(array $strings1, array $strings2, int $start1, int $start2, bool $relaxedTags): array
    {
        $end1   = count($strings1) - 1;
        $end2   = count($strings2) - 1;

        loop:
        if ($end1 < $start1 || $end2 < $start2) {
            return [$end1, $end2];
        }

        if ($strings1[$end1] === $this->IGNORE_STR && $strings2[$end2] !== $this->IGNORE_STR &&
            ($end1 - 1) >= $start1 && $strings1[$end1 - 1] === $strings2[$end2]) {
            $end1--;
        }

        if (!$this->strCmp($strings1[$end1], $strings2[$end2], $relaxedTags)) {
            return [$end1, $end2];
        }
        $end1--;
        $end2--;
        goto loop; // This feels so wrong, yet so right...

        return []; // Fake return for phpstan
    }

    /**
     * @param string[] $strings1
     * @param string[] $strings2
     * @param bool $relaxedTags (for matching LIs even when some attributes are different
     * @return array
     */
    public function compareArrays(array $strings1, array $strings2, bool $relaxedTags): array
    {
        list($start1, $start2) = $this->getArrayDiffStarts($strings1, $strings2, $relaxedTags);
        list($end1, $end2) = $this->getArrayDiffEnds($strings1, $strings2, $start1, $start2, $relaxedTags);

        // skip any common suffix
        while ($end1 >= $start1 && $end2 >= $start2 && $this->strCmp($strings1[$end1], $strings2[$end2], $relaxedTags)) {
            $end1--;
            $end2--;
        }

        // compute the table of longest common subsequence lengths
        $table = self::computeTable($strings1, $strings2, $start1, $start2, $end1, $end2, $relaxedTags);

        // generate the partial diff
        $partialDiff = self::generatePartialDiff($table, $strings1, $strings2, $start1, $start2, $relaxedTags);

        // generate the full diff
        $diff = [];
        for ($index = 0; $index < $start1; $index++) {
            $diff[] = [$strings1[$index], self::UNMODIFIED];
        }
        while (count($partialDiff) > 0) {
            $diff[] = array_pop($partialDiff);
        }
        for ($index = $end1 + 1;
             $index < count($strings1);
             $index++) {
            $diff[] = [$strings1[$index], self::UNMODIFIED];
        }

        // return the diff
        return $diff;
    }

    /**
     * @return InsDelGroup[]
     */
    private static function findInsDelGroups(array $diff): array
    {
        /** @var InsDelGroup[] $groups */
        $groups       = [];

        $pendingSince = null;
        $pendingType  = null;
        for ($i = 0; $i < count($diff); $i++) {
            if ($diff[$i][1] == self::INSERTED) {
                if (!$pendingSince) {
                    $pendingSince = $i;
                    $pendingType  = self::INSERTED;
                } elseif ($pendingType != self::INSERTED) {
                    $group        = new InsDelGroup();
                    $group->start = $pendingSince;
                    $group->end   = $i - 1;
                    $group->type  = $pendingType;
                    $groups[] = $group;

                    $pendingSince = $i;
                    $pendingType = self::INSERTED;
                }
            } elseif ($diff[$i][1] == self::DELETED) {
                if (!$pendingSince) {
                    $pendingSince = $i;
                    $pendingType  = self::DELETED;
                } elseif ($pendingType != self::DELETED) {
                    $group        = new InsDelGroup();
                    $group->start = $pendingSince;
                    $group->end   = $i - 1;
                    $group->type  = $pendingType;
                    $groups[] = $group;

                    $pendingSince = $i;
                    $pendingType  = self::DELETED;
                }
            } else {
                if ($pendingSince) {
                    $group        = new InsDelGroup();
                    $group->start = $pendingSince;
                    $group->end   = $i - 1;
                    $group->type  = $pendingType;
                    $groups[] = $group;

                    $pendingSince = null;
                    $pendingType  = null;
                }
            }
        }
        if ($pendingSince) {
            $group        = new InsDelGroup();
            $group->start = $pendingSince;
            $group->end   = $i - 1;
            $group->type  = $pendingType;
            $groups[] = $group;
        }

        return $groups;
    }


    /**
     * Fixes problems like this:
     * <p><ins>Some text</p><p></ins> (tokenized)
     * =>
     * <ins><p>Some text</p></ins><p>
     */
    public static function shiftMisplacedHTMLTags(array $diff): array
    {
        $groups = self::findInsDelGroups($diff);

        $forwardShiftingTags = ['<p>', '<ul>', '<ol>', '<blockquote>'];
        foreach ($groups as $group) {
            $start = $group->start;
            $end   = $group->end;
            if ($start === 0) {
                continue;
            }
            if ($diff[$start - 1][1] != self::UNMODIFIED) {
                continue;
            }
            $prevTag = $diff[$start - 1][0];
            $lastTag = $diff[$end][0];
            if (in_array($prevTag, $forwardShiftingTags) && $prevTag == $lastTag) {
                $diff[$start - 1][1] = $diff[$end][1];
                $diff[$end][1]       = self::UNMODIFIED;
            }
        }

        $groups = self::findInsDelGroups($diff);

        $backwardShiftingTags = ['</p>', '</ul>', '</ol>', '</blockquote>'];
        foreach ($groups as $group) {
            $start = $group->start;
            $end   = $group->end;
            if ($end === count($diff) - 1) {
                continue;
            }
            if ($diff[$end + 1][1] != self::UNMODIFIED) {
                continue;
            }
            $nextTag  = $diff[$end + 1][0];
            $firstTag = $diff[$start][0];
            if (in_array($nextTag, $backwardShiftingTags) && $nextTag == $firstTag) {
                $diff[$end + 1][1] = $diff[$start][1];
                $diff[$start][1]   = self::UNMODIFIED;
            }
        }

        return $diff;
    }

    private function moveWordOpsToMatchSentenceStructureWrapperBlock(array $words, int $mode, int $fromIdx, int $toIdx): array
    {
        $cnt = 0;

        while (($fromIdx - $cnt) > 0 && ($toIdx - $cnt) > $fromIdx &&
            $words[$fromIdx - $cnt - 1][1] === Engine::UNMODIFIED &&
            $this->strCmp($words[$fromIdx - $cnt - 1][0], $words[$toIdx - $cnt][0], false) &&
            !str_contains($words[$toIdx - $cnt][0], '<')   && !str_contains($words[$toIdx - $cnt][0], '>')   &&
            !str_contains($words[$toIdx - $cnt][0], '.')
        ) {
            $words[$fromIdx - $cnt - 1][1] = $mode;
            $words[$toIdx - $cnt][1]       = Engine::UNMODIFIED;
            $cnt++;
        }

        return $words;
    }

    public function moveWordOpsToMatchSentenceStructure(array $words): array
    {
        $lastMode   = null;
        $blockStart = null;
        $blocks     = [];

        for ($i = 0; $i < count($words); $i++) {
            if ($words[$i][1] == Engine::UNMODIFIED) {
                if ($lastMode !== null && $lastMode !== Engine::UNMODIFIED) {
                    $blocks[] = [$lastMode, $blockStart, $i - 1];
                }
                $blockStart = $i;
                $lastMode   = Engine::UNMODIFIED;
            } else {
                if ($lastMode !== $words[$i][1]) {
                    if ($lastMode !== null && $lastMode !== Engine::UNMODIFIED) {
                        $blocks[] = [$lastMode, $blockStart, $i - 1];
                    }
                    $blockStart = $i;
                    $lastMode   = $words[$i][1];
                }
            }
        }
        if ($lastMode !== null && $lastMode !== Engine::UNMODIFIED) {
            $blocks[] = [$lastMode, $blockStart, $i - 1];
        }

        foreach ($blocks as $block) {
            $words = $this->moveWordOpsToMatchSentenceStructureWrapperBlock($words, $block[0], $block[1], $block[2]);
        }

        return $words;
    }

    /**
     * Returns the table of longest common subsequence lengths for the specified
     * sequences. The parameters are:
     *
     * @param string[] $sequence1 - the first sequence
     * @param string[] $sequence2 - the second sequence
     * @param int $start1         - the first starting index
     * @param int $start2         - the second starting index
     * @param int $end1         - the ending index for the first sequence
     * @param int $end2         - the ending index for the second sequence
     * @param bool $relaxedTags
     * @return array
     */
    private function computeTable(array $sequence1, array $sequence2, int $start1, int $start2, int $end1, int $end2, bool $relaxedTags): array
    {
        // determine the lengths to be compared
        $length1 = $end1 - $start1 + 1;
        $length2 = $end2 - $start2 + 1;

        // initialise the table
        $table = [array_fill(0, $length2 + 1, 0)];

        // loop over the rows
        for ($index1 = 1; $index1 <= $length1; $index1++) {
            // create the new row
            $table[$index1] = [0];

            // loop over the columns
            for ($index2 = 1; $index2 <= $length2; $index2++) {
                // store the longest common subsequence length
                if ($this->strCmp($sequence1[$index1 + $start1 - 1], $sequence2[$index2 + $start2 - 1], $relaxedTags)) {
                    $table[$index1][$index2] = $table[$index1 - 1][$index2 - 1] + 1;
                } else {
                    $table[$index1][$index2] = max($table[$index1 - 1][$index2], $table[$index1][$index2 - 1]);
                }

            }
        }
        // return the table
        return $table;
    }

    /**
     * Returns the partial diff for the specified sequences, in reverse order.
     * The parameters are:
     *
     * @param array $table     - the table returned by the computeTable function
     * @param array $sequence1 - the first sequence
     * @param array $sequence2 - the second sequence
     * @param int $start1      - the first starting index
     * @param int $start2      - the second starting index
     */
    private function generatePartialDiff(array $table, array $sequence1, array $sequence2, int $start1, int $start2, bool $relaxedTags): array
    {
        //  initialise the diff
        $diff = [];

        // initialise the indices
        $index1 = count($table) - 1;
        $index2 = count($table[0]) - 1;

        // loop until there are no items remaining in either sequence
        while ($index1 > 0 || $index2 > 0) {
            // check what has happened to the items at these indices
            if ($index1 > 0 && $index2 > 0
                && $this->strCmp($sequence1[$index1 + $start1 - 1], $sequence2[$index2 + $start2 - 1], $relaxedTags)
            ) {
                // update the diff and the indices
                $diff[] = [$sequence1[$index1 + $start1 - 1], self::UNMODIFIED];
                $index1--;
                $index2--;
            } elseif ($index2 > 0
                && $table[$index1][$index2] == $table[$index1][$index2 - 1]
            ) {
                // update the diff and the indices
                $diff[] = [$sequence2[$index2 + $start2 - 1], self::INSERTED];
                $index2--;
            } else {
                // update the diff and the indices
                $diff[] = [$sequence1[$index1 + $start1 - 1], self::DELETED];
                $index1--;
            }

        }

        // return the diff
        return $diff;
    }
}
