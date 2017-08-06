<?php

namespace app\components\diff;

use app\components\HashedStaticCache;
use app\models\exceptions\Internal;

class Diff
{
    const MAX_LINE_CHANGE_RATIO_MIN_LEN = 100;
    const MAX_LINE_CHANGE_RATIO         = 0.6;
    const MAX_LINE_CHANGE_RATIO_PART    = 0.4;

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
     * @return string
     */
    public static function normalizeForDiff($line)
    {
        return preg_replace("/<br>\\n+/siu", "<br>", $line);
    }

    /**
     * @param string $line
     * @return string[]
     */
    public static function tokenizeLine($line)
    {
        $line    = static::normalizeForDiff($line);
        $htmlTag = '/(<br>\n+|<[^>]+>)/siu';
        $arr     = preg_split($htmlTag, $line, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out     = [];
        foreach ($arr as $arr2) {
            if (preg_match($htmlTag, $arr2)) {
                $out[] = $arr2;
            } else {
                foreach (preg_split('/([ \-\.\:])/', $arr2, -1, PREG_SPLIT_DELIM_CAPTURE) as $tok) {
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
        $out2 = [];
        foreach ($out as $word) {
            if ($word != '') {
                $out2[] = $word;
            }
        }
        return $out2;
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
            return $linenumber . $preWords . $preChars .
                $this->wrapWithDelete($restDelC) . $this->wrapWithInsert($restInsC) .
                $postChars . $postWords;
        }
        return $linenumber . $preWords . $this->wrapWithDelete($preChars . $restDelC . $postChars) .
            $this->wrapWithInsert($preChars . $restInsC . $postChars) . $postWords;
    }

    /**
     * @param $lineOldArr
     * @param $lineNewArr
     * @return boolean
     */
    public function htmlParagraphTypeChanges($lineOldArr, $lineNewArr)
    {
        if (count($lineOldArr) === 0 || count($lineNewArr) === 0) {
            return false;
        }
        if (!preg_match('/^<(?<nodeType>\w+)[ >]/siu', $lineOldArr[0], $matches)) {
            return false;
        } else {
            $nodeType1 = $matches['nodeType'];
        }
        if (!preg_match('/^<(?<nodeType>\w+)[ >]/siu', $lineNewArr[0], $matches)) {
            return false;
        } else {
            $nodeType2 = $matches['nodeType'];
        }
        return ($nodeType1 != $nodeType2);
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
        $lineOld      = static::normalizeForDiff($lineOld);
        $lineNew      = static::normalizeForDiff($lineNew);
        $lineOldArr   = static::tokenizeLine($lineOld);
        $lineNewArr   = static::tokenizeLine($lineNew);

        if ($this->htmlParagraphTypeChanges($lineOldArr, $lineNewArr)) {
            return $this->wrapWithDelete($lineOld) . $this->wrapWithInsert($lineNew);
        }

        $return = $this->engine->compareArrays($lineOldArr, $lineNewArr);
        $return = $this->engine->moveWordOpsToMatchSentenceStructure($return);

        $return = $this->groupOperations($return, '');

        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStrs[] = $return[$i][0];
            } elseif ($return[$i][1] == Engine::DELETED) {
                if (isset($return[$i + 1]) && $return[$i + 1][1] == Engine::INSERTED &&
                    strlen($return[$i + 1][0]) > 0 && $return[$i + 1][0][0] != '<'
                ) {
                    $computedStrs[] = $this->computeWordDiff($return[$i][0], $return[$i + 1][0]);
                    $i++;
                } else {
                    $delParts = explode(' ', $return[$i][0]);
                    for ($j = 0; $j < count($delParts) - 1; $j++) {
                        $delParts[$j] .= ' ';
                    }
                    foreach ($delParts as $delPart) {
                        $computedStrs[] = $this->wrapWithDelete($delPart);
                    }
                }
            } elseif ($return[$i][1] == Engine::INSERTED) {
                $insParts = explode(' ', $return[$i][0]);
                for ($j = 0; $j < count($insParts) - 1; $j++) {
                    $insParts[$j] .= ' ';
                }
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

        // If too much of the whole paragraph changes, then we don't display an inline diff anymore
        if (strpos($combined, DiffRenderer::DEL_START) !== false &&
            strpos($combined, DiffRenderer::INS_START) !== false
        ) {
            $changeRatio = $this->computeLineDiffChangeRatio($lineOld, $combined);
            if ($changeRatio > static::MAX_LINE_CHANGE_RATIO) {
                return $this->wrapWithDelete($lineOld) . $this->wrapWithInsert($lineNew);
            }
        }

        $split = $this->getUnchangedPrefixPostfix($lineOld, $lineNew, $combined);
        list($prefix, $middleOrig, $middleNew, $middleDiff, $postfix) = $split;

        $middleLen  = mb_strlen(str_replace('###LINENUMBER###', '', $middleOrig));
        $breaksList = (mb_stripos($middleDiff, '</li>') !== false);
        if ($middleLen > static::MAX_LINE_CHANGE_RATIO_MIN_LEN && !$breaksList) {
            $changeRatio = $this->computeLineDiffChangeRatio($middleOrig, $middleDiff);
            if ($changeRatio > static::MAX_LINE_CHANGE_RATIO_PART) {
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
     * @param string $haystack
     * @param string $needle
     * @return int|false
     */
    public static function findFirstOccurrenceIgnoringTags($haystack, $needle)
    {
        $first = mb_strpos($haystack, $needle);
        if ($first === false) {
            return false;
        }
        $firstTag = mb_strpos($haystack, '<');
        if ($firstTag === false || $firstTag > $first) {
            return $first;
        }
        $parts = preg_split('/(<[^>]*>)/', $haystack, -1, PREG_SPLIT_DELIM_CAPTURE);
        $pos   = 0;
        for ($i = 0; $i < count($parts); $i++) {
            if (($i % 2) == 0) {
                $occ = mb_strpos($parts[$i], $needle);
                if ($occ !== false) {
                    return $pos + $occ;
                }
            }
            $pos += mb_strlen($parts[$i]);
        }
        return false;
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
            $firstDot = static::findFirstOccurrenceIgnoringTags($postfix, '. ');
            if ($postfixLen > 40 && $firstDot !== false && $firstDot < 40) {
                $postfix = mb_substr($postfix, $firstDot + 1);
            } else {
                $firstDot = static::findFirstOccurrenceIgnoringTags($postfix, '.');
                if ($postfixLen > 40 && $firstDot !== false && $firstDot < 40) {
                    $postfix = mb_substr($postfix, $firstDot + 1);
                }
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
        $cache_deps = [$referenceParas, $newParas, $diffFormatting];
        $cached     = HashedStaticCache::getCache('compareHtmlParagraphs', $cache_deps);
        if ($cached) {
            return $cached;
        }

        $matcher = new ArrayMatcher();
        $matcher->addIgnoredString('###LINENUMBER###');
        $this->setIgnoreStr('###LINENUMBER###');
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
            $diffS = str_replace('<del>###LINENUMBER###</del>', '###LINENUMBER###', $diffS);
            $diffS = str_replace(
                '<del style="color: red; text-decoration: line-through;">###LINENUMBER###</del>',
                '###LINENUMBER###',
                $diffS
            );
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
        if ($pendingInsert) {
            $resolved[] = $pendingInsert;
        }

        $resolved = MovingParagraphDetector::markupMovedParagraphs($resolved);

        HashedStaticCache::setCache('compareHtmlParagraphs', $cache_deps, $resolved);

        return $resolved;
    }

    /**
     * @param string $diff
     * @param array $amParams
     * @return array
     */
    public function convertToWordArray($diff, $amParams)
    {
        $splitChars        = [' ', '-', '>', '<', ':', '.'];
        $words             = [
            0 => [
                'word' => '',
                'diff' => '',
            ]
        ];
        $diffPartArr       = preg_split('/(###(?:INS|DEL)_(?:START|END)###)/siu', $diff, -1, PREG_SPLIT_DELIM_CAPTURE);
        $inDel             = $inIns = false;
        $originalWordPos   = 0;
        $pendingOpeningDel = false;
        foreach ($diffPartArr as $diffPart) {
            if ($diffPart == '###INS_START###') {
                $words[$originalWordPos]['diff'] .= $diffPart;
                $words[$originalWordPos]         = array_merge($words[$originalWordPos], $amParams);
                $inIns                           = true;
            } elseif ($diffPart == '###INS_END###') {
                $words[$originalWordPos]['diff'] .= $diffPart;
                $words[$originalWordPos]         = array_merge($words[$originalWordPos], $amParams);
                $inIns                           = false;
            } elseif ($diffPart == '###DEL_START###') {
                $inDel             = true;
                $pendingOpeningDel = true;
            } elseif ($diffPart == '###DEL_END###') {
                $words[$originalWordPos]['diff'] .= $diffPart;
                $words[$originalWordPos]         = array_merge($words[$originalWordPos], $amParams);
                $inDel                           = false;
            } else {
                $diffPartWords = static::tokenizeLine($diffPart);
                if ($inIns) {
                    $words[$originalWordPos]['diff'] .= implode('', $diffPartWords);
                    $words[$originalWordPos]         = array_merge($words[$originalWordPos], $amParams);
                } elseif ($inDel) {
                    foreach ($diffPartWords as $diffPartWord) {
                        $prevLastChar = mb_substr($words[$originalWordPos]['word'], -1, 1);
                        $isNewWord    = (
                            in_array($prevLastChar, $splitChars) ||
                            (in_array($diffPartWord, $splitChars) && $diffPartWord != ' ' && $diffPartWord != '-') ||
                            $diffPartWord[0] == '<'
                        );
                        if ($isNewWord || $originalWordPos == 0) {
                            $originalWordPos++;
                            $words[$originalWordPos] = [
                                'word' => '',
                                'diff' => '',
                            ];
                        }
                        $words[$originalWordPos]['word'] .= $diffPartWord;
                        if ($pendingOpeningDel) {
                            $words[$originalWordPos]['diff'] .= '###DEL_START###';
                            $pendingOpeningDel               = false;
                        }
                        $words[$originalWordPos]['diff'] .= $diffPartWord;
                        $words[$originalWordPos]         = array_merge($words[$originalWordPos], $amParams);
                    }
                } else {
                    foreach ($diffPartWords as $diffPartWord) {
                        $prevLastChar = mb_substr($words[$originalWordPos]['word'], -1, 1);
                        $isNewWord    = (
                            in_array($prevLastChar, $splitChars) ||
                            (in_array($diffPartWord, $splitChars) && $diffPartWord != ' ' && $diffPartWord != '-') ||
                            $diffPartWord[0] == '<'
                        );

                        if ($isNewWord || $originalWordPos == 0) {
                            $originalWordPos++;
                            $words[$originalWordPos] = [
                                'word' => '',
                                'diff' => '',
                            ];
                        }
                        $words[$originalWordPos]['word'] .= $diffPartWord;
                        $words[$originalWordPos]['diff'] .= $diffPartWord;
                    }
                }
            }
        }

        $first = array_shift($words);
        if (count($words) == 0) {
            return [$first];
        } else {
            $words[0]['diff'] = $first['diff'] . $words[0]['diff'];
            return $words;
        }
    }

    /**
     * @param string $orig
     * @param array $wordArr
     * @throws Internal
     */
    public function checkWordArrayConsistency($orig, $wordArr)
    {
        $origArr = self::tokenizeLine($orig);
        if (count($origArr) == 0 && count($wordArr) == 1) {
            return;
        }
        for ($i = 0; $i < count($wordArr); $i++) {
            if (!isset($origArr[$i])) {
                var_dump($wordArr);
                var_dump($origArr);
                throw new Internal('Only exists in Diff-wordArray: ' . print_r($wordArr[$i]) . ' (Pos: ' . $i . ')');
            }
            if ($origArr[$i] != $wordArr[$i]['word']) {
                var_dump($wordArr);
                var_dump($origArr);
                throw new Internal('Inconsistency; first difference at pos: ' . $i .
                    ' ("' . $origArr[$i] . '" vs. "' . $wordArr[$i]['word'] . '")');
            }
        }
        if (count($wordArr) != count($origArr)) {
            var_dump($wordArr);
            var_dump($origArr);
            throw new Internal('Unequal size of arrays, but equal at beginning');
        }
    }

    /**
     * @param string[] $referenceParas
     * @param string[] $newParas
     * @param array $amParams
     * @throws Internal
     * @return array
     */
    public function compareHtmlParagraphsToWordArray($referenceParas, $newParas, $amParams = [])
    {
        $matcher = new ArrayMatcher();
        $matcher->addIgnoredString('###LINENUMBER###');
        list($adjustedRef, $adjustedMatching) = $matcher->matchForDiff($referenceParas, $newParas);
        if (count($adjustedRef) != count($adjustedMatching)) {
            throw new Internal('compareSectionedHtml: number of sections does not match');
        }

        $diffSections  = [];
        $pendingInsert = '';
        for ($i = 0; $i < count($adjustedRef); $i++) {
            if ($adjustedRef[$i] == '###EMPTYINSERTED###') {
                $diffLine  = $this->computeLineDiff('', $adjustedMatching[$i]);
                $wordArray = $this->convertToWordArray($diffLine, $amParams);
                if (count($wordArray) != 1 || $wordArray[0]['word'] != '') {
                    throw new Internal('Inserted Paragraph Incosistency');
                }
                if (count($diffSections) == 0) {
                    $pendingInsert .= $wordArray[0]['diff'];
                } else {
                    $last                                 = count($diffSections) - 1;
                    $lastEl                               = count($diffSections[$last]) - 1;
                    $diffSections[$last][$lastEl]['diff'] .= $wordArray[0]['diff'];
                    $diffSections[$last][$lastEl]         = array_merge($diffSections[$last][$lastEl], $amParams);

                }
            } else {
                $origLine    = $adjustedRef[$i];
                $matchingRow = str_replace('###EMPTYINSERTED###', '', $adjustedMatching[$i]);
                $diffLine    = $this->computeLineDiff($origLine, $matchingRow);
                $wordArray   = $this->convertToWordArray($diffLine, $amParams);

                $this->checkWordArrayConsistency($origLine, $wordArray);
                if ($pendingInsert != '') {
                    $wordArray[0]['diff'] = $pendingInsert . $wordArray[0]['diff'];
                    $wordArray[0]         = array_merge($wordArray[0], $amParams);
                    $pendingInsert        = '';
                }
                $diffSections[] = $wordArray;
            }
        }
        return $diffSections;
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
