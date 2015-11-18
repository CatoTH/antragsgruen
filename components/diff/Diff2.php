<?php

namespace app\components\diff;

use app\models\exceptions\Internal;

class Diff2
{
    const MAX_LINE_CHANGE_RATIO_MIN_LEN = 100;
    const MAX_LINE_CHANGE_RATIO         = 0.4;

    // # is necessary for placeholders like ###LINENUMBER###
    public static $WORD_BREAKING_CHARS = [' ', ',', '.', '#', '-', '?', '!', ':', '<', '>'];

    /** @var Engine */
    private $engine;

    /**
     */
    public function __construct()
    {
        $this->engine = new Engine();
    }

    /**
     * @param string $str
     */
    public function setIgnoreStr($str)
    {
        $this->engine->setIgnoreStr($str);
    }

    /**
     * @param string $str
     * @return string
     */
    public static function wrapWithInsert($str)
    {
        if ($str == '') {
            return '';
        } else {
            return DiffRenderer::INS_START . $str . DiffRenderer::INS_END;
        }

    }

    /**
     * @param string $str
     * @return string
     */
    public static function wrapWithDelete($str)
    {
        if ($str == '') {
            return '';
        } else {
            return DiffRenderer::DEL_START . $str . DiffRenderer::DEL_END;
        }
    }

    /**
     * @param string $line
     * @return string[]
     */
    public static function tokenizeLine($line)
    {
        $htmlTag = '/(<[^>]+>)/siuU';
        $arr     = preg_split($htmlTag, $line, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out     = [];
        foreach ($arr as $arr2) {
            if (preg_match($htmlTag, $arr2)) {
                $out[] = $arr2;
            } else {
                foreach (preg_split('/([ \-])/', $arr2, -1, PREG_SPLIT_DELIM_CAPTURE) as $tok) {
                    if ($tok == ' ' || $tok == '-') {
                        if (count($out) == 0) {
                            $out[] = $tok;
                        } else {
                            $out[count($out) - 1] .= $tok;
                        }
                    } else {
                        $out[] = $tok;
                    }
                }
            }
        }
        return $out;
    }

    /**
     * @param string[] $lines
     * @return string
     */
    public static function untokenizeLine($lines)
    {
        return implode('', $lines);
    }

    /**
     * @param array $operations
     * @param string $groupBy
     * @return array
     */
    public function groupOperations($operations, $groupBy)
    {
        $return = [];

        $preOp        = null;
        $preList      = false;
        $currentSpool = [];
        foreach ($operations as $operation) {
            $firstfour = mb_substr($operation[0], 0, 4);
            $isList    = $firstfour == '<ul>' || $firstfour == '<ol>';
            if (preg_match('/^<[^>]*>$/siu', $operation[0]) && $operation[0] != '</pre>') {
                if (count($currentSpool) > 0) {
                    $return[] = [implode($groupBy, $currentSpool), $preOp];
                }
                $return[]     = [
                    $operation[0],
                    $operation[1],
                ];
                $preOp        = null;
                $currentSpool = [];
            } elseif ($operation[1] !== $preOp || $isList || $preList) {
                if (count($currentSpool) > 0) {
                    $return[] = [
                        implode($groupBy, $currentSpool),
                        $preOp
                    ];
                }
                $preOp        = $operation[1];
                $currentSpool = [$operation[0]];
            } else {
                $currentSpool[] = $operation[0];
            }
            $preList = $isList;
        }
        if (count($currentSpool) > 0) {
            $return[] = [
                implode($groupBy, $currentSpool),
                $preOp
            ];
        }

        return $return;
    }

    /**
     * @param string $word1
     * @param string $word2
     * @return string
     */
    private function getCommonPrefix($word1, $word2)
    {
        $len1 = mb_strlen($word1);
        $len2 = mb_strlen($word2);
        $min  = min($len1, $len2);
        $str  = '';
        for ($i = 0; $i <= $min; $i++) {
            $char1 = mb_substr($word1, $i, 1);
            $char2 = mb_substr($word2, $i, 1);
            if ($char1 != $char2) {
                return $str;
            } else {
                $str .= $char1;
            }
        }
        return $str;
    }

    /**
     * @param string $word1
     * @param string $word2
     * @return string
     */
    private function getCommonSuffix($word1, $word2)
    {
        $len1 = mb_strlen($word1);
        $len2 = mb_strlen($word2);
        $min  = min($len1, $len2);
        $str  = '';
        for ($i = 0; $i <= $min; $i++) {
            $char1 = mb_substr($word1, $len1 - $i, 1);
            $char2 = mb_substr($word2, $len2 - $i, 1);
            if ($char1 != $char2) {
                return $str;
            } else {
                $str = $char1 . $str;
            }
        }
        return $str;
    }


    /**
     * @param string $word1
     * @param string $word2
     * @return string
     */
    private function getCommonWordPrefix($word1, $word2)
    {
        $prefix      = $this->getCommonPrefix($word1, $word2);
        $len         = mb_strlen($prefix);
        $preLen      = mb_strlen($prefix);
        $endsInWords = false;
        if (mb_strlen($word1) > $preLen && !in_array(mb_substr($word1, $preLen, 1), static::$WORD_BREAKING_CHARS)) {
            $endsInWords = true;
        }
        if (mb_strlen($word2) > $preLen && !in_array(mb_substr($word2, $preLen, 1), static::$WORD_BREAKING_CHARS)) {
            $endsInWords = true;
        }
        if ($endsInWords) {
            for ($i = 0; $i <= $len; $i++) {
                $char1 = mb_substr($prefix, $len - $i, 1);
                if (in_array($char1, static::$WORD_BREAKING_CHARS)) {
                    return mb_substr($prefix, 0, $len - $i + 1);
                }
            }
            return '';
        } else {
            return $prefix;
        }
    }

    /**
     * @param string $word1
     * @param string $word2
     * @return string
     */
    private function getCommonWordSuffix($word1, $word2)
    {
        $suffix       = $this->getCommonSuffix($word1, $word2);
        $w1len        = mb_strlen($word1);
        $w2len        = mb_strlen($word2);
        $postLen      = mb_strlen($suffix);
        $startsInWord = false;
        if ($w1len > $postLen && !in_array(mb_substr($word1, $w1len - $postLen - 1, 1), static::$WORD_BREAKING_CHARS)) {
            $startsInWord = true;
        }
        if ($w2len > $postLen && !in_array(mb_substr($word2, $w2len - $postLen - 1, 1), static::$WORD_BREAKING_CHARS)) {
            $startsInWord = true;
        }
        if ($startsInWord) {
            $len = mb_strlen($suffix);
            for ($i = 0; $i < $len; $i++) {
                $char1 = mb_substr($suffix, $i, 1);
                if (in_array($char1, static::$WORD_BREAKING_CHARS)) {
                    return mb_substr($suffix, $i);
                }
            }
            return '';
        } else {
            return $suffix;
        }
    }

    /**
     * @param string $wordDel
     * @param string $wordInsert
     * @return string
     */
    public function computeWordDiff($wordDel, $wordInsert)
    {
        if (mb_substr($wordDel, 0, 16) == '###LINENUMBER###' && mb_substr($wordInsert, 0, 16) != '###LINENUMBER###') {
            $linenumber = '###LINENUMBER###';
            $wordDel    = mb_substr($wordDel, 16);
        } else {
            $linenumber = '';
        }
        $preWords = $this->getCommonWordPrefix($wordDel, $wordInsert);
        $restDel  = mb_substr($wordDel, mb_strlen($preWords));
        $restIns  = mb_substr($wordInsert, mb_strlen($preWords));

        $postWords = $this->getCommonWordSuffix($restDel, $restIns);
        $restDel   = mb_substr($restDel, 0, mb_strlen($restDel) - mb_strlen($postWords));
        $restIns   = mb_substr($restIns, 0, mb_strlen($restIns) - mb_strlen($postWords));


        $preChars = $this->getCommonPrefix($restDel, $restIns);
        if (mb_strlen($preChars) < 3) {
            $preChars = '';
        }
        $restDelC = mb_substr($restDel, mb_strlen($preChars));
        $restInsC = mb_substr($restIns, mb_strlen($preChars));

        $postChars = $this->getCommonSuffix($restDelC, $restInsC);
        if (mb_strlen($postChars) < 3) {
            $postChars = '';
        }
        $restDelC = mb_substr($restDelC, 0, mb_strlen($restDelC) - mb_strlen($postChars));
        $restInsC = mb_substr($restInsC, 0, mb_strlen($restInsC) - mb_strlen($postChars));

        if (mb_strlen($restDelC) <= 3 && mb_strlen($restInsC) <= 3) {
            return $linenumber . $preWords . $preChars . $this->wrapWithDelete($restDelC) . $this->wrapWithInsert($restInsC) .
            $postChars . $postWords;
        }
        return $linenumber . $preWords . $this->wrapWithDelete($preChars . $restDelC . $postChars) .
        $this->wrapWithInsert($preChars . $restInsC . $postChars) . $postWords;
    }

    /**
     * @param string $lineOld
     * @param string $lineNew
     * @return string
     * @throws Internal
     */
    public function computeLineDiff($lineOld, $lineNew)
    {
        $computedStrs = [];
        $lineOldArr   = static::tokenizeLine($lineOld);
        $lineNewArr   = static::tokenizeLine($lineNew);

        $return = $this->engine->compareArrays($lineOldArr, $lineNewArr);

        $return = $this->groupOperations($return, '');

        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStrs[] = $return[$i][0];
            } elseif ($return[$i][1] == Engine::DELETED) {
                if (
                    isset($return[$i + 1]) && $return[$i + 1][1] == Engine::INSERTED &&
                    strlen($return[$i + 1][0]) > 0 && $return[$i + 1][0][0] != '<'
                ) {
                    $computedStrs[] = $this->computeWordDiff($return[$i][0], $return[$i + 1][0]);
                    $i++;
                } else {
                    $delParts = explode("\n", str_replace(" ", " \n", $return[$i][0]));
                    foreach ($delParts as $delPart) {
                        $computedStrs[] = $this->wrapWithDelete($delPart);
                    }
                }
            } elseif ($return[$i][1] == Engine::INSERTED) {
                $insParts = explode("\n", str_replace(" ", " \n", $return[$i][0]));
                foreach ($insParts as $insPart) {
                    $computedStrs[] = $this->wrapWithInsert($insPart);
                }
            } else {
                throw new Internal('Unknown type: ' . $return[$i][1]);
            }
        }

        $combined = static::untokenizeLine($computedStrs);
        $combined = str_replace(DiffRenderer::DEL_END . DiffRenderer::DEL_START, '', $combined);
        $combined = str_replace(DiffRenderer::INS_END . DiffRenderer::INS_START, '', $combined);


        $split = $this->getUnchangedPrefixPostfix($lineOld, $lineNew, $combined);
        list($prefix, $middleOrig, $middleNew, $middleDiff, $postfix) = $split;

        if (mb_strlen($middleOrig) > static::MAX_LINE_CHANGE_RATIO_MIN_LEN) {
            $changeRatio = $this->computeLineDiffChangeRatio($middleOrig, $middleDiff);
            if ($changeRatio > static::MAX_LINE_CHANGE_RATIO) {
                $combined = $prefix;
                $combined .= $this->wrapWithDelete($middleOrig);
                $combined .= $this->wrapWithInsert($middleNew);
                $combined .= $postfix;
            }
        }

        return $combined;
    }


    /**
     * @param array $arr
     * @param int $idx
     * @return array|null
     */
    public static function computeSubsequentInsertsDeletes($arr, $idx)
    {
        $numDeletes = 0;
        $deleteStrs = [];
        $insertStrs = [];
        while ($idx < count($arr) && $arr[$idx][1] == Engine::DELETED) {
            $deleteStrs[] = $arr[$idx][0];
            $numDeletes++;
            $idx++;
        }
        $goon = true;
        for ($i = 0; $i < $numDeletes && $goon; $i++) {
            if (!isset($arr[$idx + $i]) || $arr[$idx + $i][1] != Engine::INSERTED) {
                $goon = false;
            } else {
                $insertStrs[] = $arr[$idx + $i][0];
            }
        }
        $matcher = new ArrayMatcher();
        $matcher->addIgnoredString('###LINENUMBER###');
        $newInserts = $matcher->matchArrayResolved($deleteStrs, $insertStrs);
        return [$deleteStrs, $newInserts, count($deleteStrs) + count($insertStrs)];
    }

    /**
     * @internal
     * @param string $orig
     * @param string $new
     * @param string $diff
     * @return string[]
     */
    public function getUnchangedPrefixPostfix($orig, $new, $diff)
    {
        $firstTagOrig = (preg_match('/^<[^>]+>/siu', $orig, $matchesOrig) ? $matchesOrig[0] : '');
        $firstTagNew  = (preg_match('/^<[^>]+>/siu', $new, $matchesNew) ? $matchesNew[0] : '');
        if ($firstTagOrig != $firstTagNew) {
            return ['', $orig, $new, $diff, ''];
        }

        $parts      = preg_split('/\#\#\#(INS|DEL)_(START|END)\#\#\#/siuU', $diff);
        $prefix     = $parts[0];
        $postfix    = $parts[count($parts) - 1];
        $prefixLen  = mb_strlen($prefix);
        $postfixLen = mb_strlen($postfix);

        $prefixPre = $prefix;
        if ($prefixLen < 40) {
            $prefix = '';
        } else {
            if ($prefixLen > 0 && mb_substr($prefix, $prefixLen - 1, 1) == '.') {
                // Leave it unchanged
            } elseif ($prefixLen > 40 && mb_strrpos($prefix, '. ') > $prefixLen - 40) {
                $prefix = mb_substr($prefix, 0, mb_strrpos($prefix, '. ') + 2);
            } elseif ($prefixLen > 40 && mb_strrpos($prefix, '.') > $prefixLen - 40) {
                $prefix = mb_substr($prefix, 0, mb_strrpos($prefix, '.') + 1);
            }
        }
        if ($prefix == '') {
            if (preg_match('/^(<(p|blockquote|ul|ol|li|pre)>)+/siu', $prefixPre, $matches)) {
                $prefix = $matches[0];
            }
        }

        $postfixPre = $postfix;
        if ($postfixLen < 40) {
            $postfix = '';
        } else {
            if ($postfixLen > 40 && mb_strpos($postfix, '. ') !== false && mb_strpos($postfix, '. ') < 40) {
                $postfix = mb_substr($postfix, mb_strpos($postfix, '. ') + 1);
            } elseif ($postfixLen > 40 && mb_strpos($postfix, '.') !== false && mb_strpos($postfix, '.') < 40) {
                $postfix = mb_substr($postfix, mb_strpos($postfix, '.') + 1);
            }
        }
        if ($postfix == '') {
            if (preg_match('/(<\/(p|blockquote|ul|ol|li|pre)>)+$/siu', $postfixPre, $matches)) {
                $postfix = $matches[0];
            }
        }

        $prefixLen     = mb_strlen($prefix);
        $prefixNew     = str_replace('###LINENUMBER###', '', $prefix);
        $prefixNewLen  = mb_strlen($prefixNew);
        $postfixLen    = mb_strlen($postfix);
        $postfixNew    = str_replace('###LINENUMBER###', '', $postfix);
        $postfixNewLen = mb_strlen($postfixNew);
        $middleDiff    = mb_substr($diff, $prefixLen, mb_strlen($diff) - $prefixLen - $postfixLen);
        $middleOrig    = mb_substr($orig, $prefixLen, mb_strlen($orig) - $prefixLen - $postfixLen);
        $middleNew     = mb_substr($new, $prefixNewLen, mb_strlen($new) - $prefixNewLen - $postfixNewLen);

        return [$prefix, $middleOrig, $middleNew, $middleDiff, $postfix];
    }

    /**
     * @internal
     * @param string $orig
     * @param string $diff
     * @return float
     */
    public function computeLineDiffChangeRatio($orig, $diff)
    {
        $orig       = str_replace(['###LINENUMBER###'], [''], $orig);
        $diff       = str_replace(['###LINENUMBER###'], [''], $diff);
        $origLength = mb_strlen(strip_tags($orig));
        if ($origLength == 0) {
            return 0;
        }
        $strippedDiff = preg_replace('/\#\#\#INS_START\#\#\#(.*)\#\#\#INS_END\#\#\#/siuU', '', $diff);
        $strippedDiff = preg_replace('/\#\#\#DEL_START\#\#\#(.*)\#\#\#DEL_END\#\#\#/siuU', '', $strippedDiff);

        $strippedDiffLength = mb_strlen(strip_tags($strippedDiff));

        return 1.0 - ($strippedDiffLength / $origLength);
    }

    /**
     * @param string[] $referenceParas
     * @param string[] $newParas
     * @param int $diffFormatting
     * @return string[]
     * @throws Internal
     */
    public function compareHtmlParagraphs($referenceParas, $newParas, $diffFormatting)
    {
        $matcher = new ArrayMatcher();
        $matcher->addIgnoredString('###LINENUMBER###');
        $renderer = new DiffRenderer();
        $renderer->setFormatting($diffFormatting);

        list($adjustedRef, $adjustedMatching) = $matcher->matchForDiff($referenceParas, $newParas);
        if (count($adjustedRef) != count($adjustedMatching)) {
            throw new Internal('compareSectionedHtml: number of sections does not match');
        }
        $diffSections = [];
        for ($i = 0; $i < count($adjustedRef); $i++) {
            $diffLine       = $this->computeLineDiff($adjustedRef[$i], $adjustedMatching[$i]);
            $diffSections[] = $renderer->renderHtmlWithPlaceholders($diffLine);
        }

        $pendingInsert = '';
        $resolved      = [];
        foreach ($diffSections as $diffS) {
            if (preg_match('/<del( [^>]*)?>###EMPTYINSERTED###<\/del>/siu', $diffS)) {
                $str = preg_replace('/<del( [^>]*)?>###EMPTYINSERTED###<\/del>/siu', '', $diffS);
                if (count($resolved) > 0) {
                    $resolved[count($resolved) - 1] .= $str;
                } else {
                    $pendingInsert .= $str;
                }
            } else {
                $str           = preg_replace('/<ins( [^>]*)?>###EMPTYINSERTED###<\/ins>/siu', '', $diffS);
                $resolved[]    = $pendingInsert . $str;
                $pendingInsert = '';
            }
        }

        return $resolved;
    }

    /**
     * @param array $referenceParas
     * @param array $newParas
     * @param int $diffFormatting
     * @return array[]
     */
    public static function computeAffectedParagraphs($referenceParas, $newParas, $diffFormatting)
    {
        $diff          = new static();
        $diffParas     = $diff->compareHtmlParagraphs($referenceParas, $newParas, $diffFormatting);
        $affectedParas = [];
        foreach ($diffParas as $paraNo => $para) {
            if (DiffRenderer::paragraphContainsDiff($para) !== false) {
                $affectedParas[$paraNo] = $para;
            }
        }
        return $affectedParas;
    }
}
