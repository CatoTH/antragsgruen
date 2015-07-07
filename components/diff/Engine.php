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


    /**
     * @param string $str
     */
    public function setIgnoreStr($str)
    {
        $this->IGNORE_STR = $str;
    }


    /**
     * @param string $str1
     * @param string $str2
     * @return bool
     */
    private function strCmp($str1, $str2)
    {
        if ($this->IGNORE_STR != '') {
            $str1 = str_replace($this->IGNORE_STR, '', $str1);
            $str2 = str_replace($this->IGNORE_STR, '', $str2);
        }
        return ($str1 === $str2);
    }

    /**
     * @param string $strings1
     * @param string $strings2
     * @return array
     */
    public function compareArrays($strings1, $strings2)
    {
        // initialise the sequences and comparison start and end positions
        $start     = 0;
        $end1      = count($strings1) - 1;
        $end2      = count($strings2) - 1;

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

    /** Returns the table of longest common subsequence lengths for the specified
     * sequences. The parameters are:
     *
     * $sequence1 - the first sequence
     * $sequence2 - the second sequence
     * $start     - the starting index
     * $end1      - the ending index for the first sequence
     * $end2      - the ending index for the second sequence
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

    /** Returns the partial diff for the specificed sequences, in reverse order.
     * The parameters are:
     *
     * $table     - the table returned by the computeTable function
     * $sequence1 - the first sequence
     * $sequence2 - the second sequence
     * $start     - the starting index
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

    /** Returns a diff as a string, where unmodified lines are prefixed by '  ',
     * deletions are prefixed by '- ', and insertions are prefixed by '+ '. The
     * parameters are:
     *
     * $diff      - the diff array
     * $separator - the separator between lines; this optional parameter defaults
     *              to "\n"
     */
    public function toString($diff, $separator = "\n")
    {
        // initialise the string
        $string = '';

        // loop over the lines in the diff
        foreach ($diff as $line) {
            // extend the string with the line
            switch ($line[1]) {
                case self::UNMODIFIED:
                    $string .= '  ' . $line[0];
                    break;
                case self::DELETED:
                    $string .= '- ' . $line[0];
                    break;
                case self::INSERTED:
                    $string .= '+ ' . $line[0];
                    break;
            }

            // extend the string with the separator
            $string .= $separator;

        }

        // return the string
        return $string;
    }

    /** Returns a diff as an HTML string, where unmodified lines are contained
     * within 'span' elements, deletions are contained within 'del' elements, and
     * insertions are contained within 'ins' elements. The parameters are:
     *
     * $diff      - the diff array
     * $separator - the separator between lines; this optional parameter defaults
     *              to '<br>'
     */
    public function toHTML($diff, $separator = '<br>')
    {
        // initialise the HTML
        $html = '';

        // loop over the lines in the diff
        foreach ($diff as $line) {
            // extend the HTML with the line
            switch ($line[1]) {
                case self::UNMODIFIED:
                    $element = 'span';
                    break;
                case self::DELETED:
                    $element = 'del';
                    break;
                case self::INSERTED:
                    $element = 'ins';
                    break;
            }
            /** @var string $element */
            $html .=
                '<' . $element . '>'
                . htmlspecialchars($line[0])
                . '</' . $element . '>';

            // extend the HTML with the separator
            $html .= $separator;

        }

        // return the HTML
        return $html;
    }

    /** Returns a diff as an HTML table. The parameters are:
     *
     * $diff        - the diff array
     * $indentation - indentation to add to every line of the generated HTML; this
     *                optional parameter defaults to ''
     * $separator   - the separator between lines; this optional parameter
     *                defaults to '<br>'
     */
    public function toTable($diff, $indentation = '', $separator = '<br>')
    {
        // initialise the HTML
        $html = $indentation . "<table class=\"diff\">\n";

        // loop over the lines in the diff
        $index = 0;
        while ($index < count($diff)) {
            // determine the line type
            switch ($diff[$index][1]) {
                // display the content on the left and right
                case self::UNMODIFIED:
                    $leftCell  = self::getCellContent($diff, $separator, $index, self::UNMODIFIED);
                    $rightCell = $leftCell;
                    break;

                // display the deleted on the left and inserted content on the right
                case self::DELETED:
                    $leftCell  =
                        self::getCellContent($diff, $separator, $index, self::DELETED);
                    $rightCell =
                        self::getCellContent($diff, $separator, $index, self::INSERTED);
                    break;

                // display the inserted content on the right
                case self::INSERTED:
                    $leftCell  = '';
                    $rightCell =
                        self::getCellContent($diff, $separator, $index, self::INSERTED);
                    break;

            }

            /** @var string $leftCell */
            /** @var string $rightCell */

            // extend the HTML with the new row
            $html .=
                $indentation
                . "  <tr>\n"
                . $indentation
                . '    <td class="diff'
                . ($this->strCmp($leftCell, $rightCell)
                    ? 'Unmodified'
                    : ($this->strCmp($leftCell, '') ? 'Blank' : 'Deleted'))
                . '">'
                . $leftCell
                . "</td>\n"
                . $indentation
                . '    <td class="diff'
                . ($this->strCmp($leftCell, $rightCell)
                    ? 'Unmodified'
                    : ($this->strCmp($rightCell, '') ? 'Blank' : 'Inserted'))
                . '">'
                . $rightCell
                . "</td>\n"
                . $indentation
                . "  </tr>\n";

        }

        // return the HTML
        return $html . $indentation . "</table>\n";
    }

    /** Returns the content of the cell, for use in the toTable function. The
     * parameters are:
     *
     * $diff        - the diff array
     * $separator   - the separator between lines
     * $index       - the current index, passes by reference
     * $type        - the type of line
     */
    private function getCellContent($diff, $separator, &$index, $type)
    {
        // initialise the HTML
        $html = '';

        // loop over the matching lines, adding them to the HTML
        while ($index < count($diff) && $diff[$index][1] == $type) {
            $html .=
                '<span>'
                . htmlspecialchars($diff[$index][0])
                . '</span>'
                . $separator;
            $index++;
        }

        // return the HTML
        return $html;
    }
}
