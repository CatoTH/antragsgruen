<?php

namespace app\components\diff;

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

    private $IGNORE_STR = '';


    public function setIgnoreStr(string $str): void
    {
        $this->IGNORE_STR = $str;
    }

    public function getIgnoreStr(): string
    {
        return $this->IGNORE_STR;
    }


    private function strCmp(string $str1, string $str2): bool
    {
        if ($this->IGNORE_STR !== '') {
            $str1 = str_replace($this->IGNORE_STR, '', $str1);
            $str2 = str_replace($this->IGNORE_STR, '', $str2);
        }

        // Ignoring some changes in pure HTML tags
        if ($str1[0] === '<' && $str2[0] === '<' && preg_match('/^<[^>]+>$/', $str1) && preg_match('/^<[^>]+>$/', $str2)) {
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
     * @param string[] $strings1
     * @param string[] $strings2
     * @return array
     */
    public function compareArrays($strings1, $strings2)
    {
        // initialise the sequences and comparison start and end positions
        $start = 0;
        $end1  = count($strings1) - 1;
        $end2  = count($strings2) - 1;

        // skip any common prefix
        while ($start <= $end1 && $start <= $end2 && $this->strCmp($strings1[$start], $strings2[$start])) {
            $start++;
        }

        // skip any common suffix
        while ($end1 >= $start && $end2 >= $start && $this->strCmp($strings1[$end1], $strings2[$end2])) {
            $end1--;
            $end2--;
        }

        // compute the table of longest common subsequence lengths
        $table = self::computeTable($strings1, $strings2, $start, $end1, $end2);

        // generate the partial diff
        $partialDiff =
            self::generatePartialDiff($table, $strings1, $strings2, $start);

        // generate the full diff
        $diff = [];
        for ($index = 0; $index < $start; $index++) {
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
     * @param $string1
     * @param $string2
     * @return array
     */
    public function compareStrings($string1, $string2)
    {
        $sequence1 = preg_split('/\R/', $string1);
        $sequence2 = preg_split('/\R/', $string2);
        return $this->compareArrays($sequence1, $sequence2);
    }

    /**
     * returns [
     *   ["start" => 1, "end" => 4, "type" => Engine::INSERTED],
     *   ["start" => 8, "end" => 10, "type" => Engine::DELETED], ...
     * ]
     *
     * @param array $diff
     * @return array
     */
    private static function findInsDelGroups($diff)
    {
        $groups       = [];
        $pendingSince = null;
        $pendingType  = null;
        for ($i = 0; $i < count($diff); $i++) {
            if ($diff[$i][1] == static::INSERTED) {
                if (!$pendingSince) {
                    $pendingSince = $i;
                    $pendingType  = static::INSERTED;
                } elseif ($pendingType != static::INSERTED) {
                    $groups[]     = [
                        'start' => $pendingSince,
                        'end'   => $i - 1,
                        'type'  => $pendingType,
                    ];
                    $pendingSince = $i;
                    $pendingType  = static::INSERTED;
                }
            } elseif ($diff[$i][1] == static::DELETED) {
                if (!$pendingSince) {
                    $pendingSince = $i;
                    $pendingType  = static::DELETED;
                } elseif ($pendingType != static::DELETED) {
                    $groups[]     = [
                        'start' => $pendingSince,
                        'end'   => $i - 1,
                        'type'  => $pendingType,
                    ];
                    $pendingSince = $i;
                    $pendingType  = static::DELETED;
                }
            } else {
                if ($pendingSince) {
                    $groups[]     = [
                        'start' => $pendingSince,
                        'end'   => $i - 1,
                        'type'  => $pendingType,
                    ];
                    $pendingSince = null;
                    $pendingType  = null;
                }
            }
        }
        if ($pendingSince) {
            $groups[] = [
                'start' => $pendingSince,
                'end'   => $i - 1,
                'type'  => $pendingType,
            ];
        }
        return $groups;
    }


    /**
     * Fixes problems like this:
     * <p><ins>Some text</p><p></ins> (tokenized)
     * =>
     * <ins><p>Some text</p></ins><p>
     *
     * @param array $diff
     * @return array
     */
    public static function shiftMisplacedHTMLTags($diff)
    {
        $groups = static::findInsDelGroups($diff);

        $forwardShiftingTags = ['<p>', '<ul>', '<ol>', '<blockquote>'];
        foreach ($groups as $group) {
            $start = $group['start'];
            $end   = $group['end'];
            if ($start == 0) {
                continue;
            }
            if ($diff[$start - 1][1] != static::UNMODIFIED) {
                continue;
            }
            $prevTag = $diff[$start - 1][0];
            $lastTag = $diff[$end][0];
            if (in_array($prevTag, $forwardShiftingTags) && $prevTag == $lastTag) {
                $diff[$start - 1][1] = $diff[$end][1];
                $diff[$end][1]       = static::UNMODIFIED;
            }
        }

        $groups = static::findInsDelGroups($diff);

        $backwardShiftingTags = ['</p>', '</ul>', '</ol>', '</blockquote>'];
        foreach ($groups as $group) {
            $start = $group['start'];
            $end   = $group['end'];
            if ($end == count($diff) - 1) {
                continue;
            }
            if ($diff[$end + 1][1] != static::UNMODIFIED) {
                continue;
            }
            $nextTag  = $diff[$end + 1][0];
            $firstTag = $diff[$start][0];
            if (in_array($nextTag, $backwardShiftingTags) && $nextTag == $firstTag) {
                $diff[$end + 1][1] = $diff[$start][1];
                $diff[$start][1]   = static::UNMODIFIED;
            }
        }

        return $diff;
    }

    /**
     * @param array $words
     * @param int $mode
     * @param int $fromIdx
     * @param int $toIdx
     * @return array
     */
    private function moveWordOpsToMatchSentenceStructureWrapperBlock($words, $mode, $fromIdx, $toIdx)
    {
        $cnt = 0;

        while (($fromIdx - $cnt) > 0 && ($toIdx - $cnt) > $fromIdx &&
            $words[$fromIdx - $cnt - 1][1] === Engine::UNMODIFIED &&
            $this->strCmp($words[$fromIdx - $cnt - 1][0], $words[$toIdx - $cnt][0]) &&
            strpos($words[$toIdx - $cnt][0], '<') === false && strpos($words[$toIdx - $cnt][0], '>') === false &&
            strpos($words[$toIdx - $cnt][0], '.') === false
        ) {
            $words[$fromIdx - $cnt - 1][1] = $mode;
            $words[$toIdx - $cnt][1]       = Engine::UNMODIFIED;
            $cnt++;
        }

        return $words;
    }

    /**
     * @param array $words
     * @return array
     */
    public function moveWordOpsToMatchSentenceStructure($words)
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
     * @param string $sequence1 - the first sequence
     * @param string $sequence2 - the second sequence
     * @param int $start        - the starting index
     * @param int $end1         - the ending index for the first sequence
     * @param int $end2         - the ending index for the second sequence
     * @return array
     */
    private function computeTable($sequence1, $sequence2, $start, $end1, $end2)
    {
        // determine the lengths to be compared
        $length1 = $end1 - $start + 1;
        $length2 = $end2 - $start + 1;

        // initialise the table
        $table = [array_fill(0, $length2 + 1, 0)];

        // loop over the rows
        for ($index1 = 1; $index1 <= $length1; $index1++) {
            // create the new row
            $table[$index1] = [0];

            // loop over the columns
            for ($index2 = 1; $index2 <= $length2; $index2++) {
                // store the longest common subsequence length
                if ($this->strCmp($sequence1[$index1 + $start - 1], $sequence2[$index2 + $start - 1])) {
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
     * Returns the partial diff for the specificed sequences, in reverse order.
     * The parameters are:
     *
     * @param array $table     - the table returned by the computeTable function
     * @param array $sequence1 - the first sequence
     * @param array $sequence2 - the second sequence
     * @param int $start       - the starting index
     * @return array
     */
    private function generatePartialDiff($table, $sequence1, $sequence2, $start)
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
                && $this->strCmp($sequence1[$index1 + $start - 1], $sequence2[$index2 + $start - 1])
            ) {
                // update the diff and the indices
                $diff[] = [$sequence1[$index1 + $start - 1], self::UNMODIFIED];
                $index1--;
                $index2--;
            } elseif ($index2 > 0
                && $table[$index1][$index2] == $table[$index1][$index2 - 1]
            ) {
                // update the diff and the indices
                $diff[] = [$sequence2[$index2 + $start - 1], self::INSERTED];
                $index2--;
            } else {
                // update the diff and the indices
                $diff[] = [$sequence1[$index1 + $start - 1], self::DELETED];
                $index1--;
            }

        }

        // return the diff
        return $diff;
    }
}
