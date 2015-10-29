<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\components\LineSplitter;
use app\models\db\AmendmentSection;
use app\models\exceptions\Internal;
use app\models\db\MotionSectionParagraphAmendment as ParagraphAmendment;

class Diff
{
    const ORIG_LINEBREAK = '###ORIGLINEBREAK###';

    const FORMATTING_CLASSES = 0;
    const FORMATTING_INLINE  = 1;

    const MAX_LINE_CHANGE_RATIO_MIN_LEN = 100;
    const MAX_LINE_CHANGE_RATIO         = 0.4;

    private $formatting = 0;

    private $debug = false;

    /** @var Engine */
    private $engine;

    /**
     */
    public function __construct()
    {
        $this->engine = new Engine();
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = $debug;
    }

    /**
     * @param int $formatting
     */
    public function setFormatting($formatting)
    {
        $this->formatting = $formatting;
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
    private function wrapWithInsert($str)
    {
        if ($str == '') {
            return $str;
        }
        if ($this->formatting == static::FORMATTING_INLINE) {
            if (mb_stripos($str, '<ul>') === 0) {
                return '<div style="color: green; margin: 0; padding: 0;"><ul class="inserted">' .
                mb_substr($str, 4) . '</div>';
            } elseif (mb_stripos($str, '<ol>') === 9) {
                return '<div style="color: green; margin: 0; padding: 0;"><ol class="inserted">' .
                mb_substr($str, 4) . '</div>';
            } elseif (mb_stripos($str, '<ul>')) {
                return '<div style="color: green; margin: 0; padding: 0;"><li class="inserted">' .
                mb_substr($str, 12) . '</div>';
            } elseif (mb_stripos($str, '<blockquote>')) {
                return '<div style="color: green; margin: 0; padding: 0;"><blockquote class="inserted">' .
                $str . '</div>';
            } else {
                return '<span style="color: green;"><ins>' . $str . '</ins></span>';
            }
        } else {
            if (mb_stripos($str, '<ul>') === 0) {
                return '<ul class="inserted">' . mb_substr($str, 4);
            } elseif (mb_stripos($str, '<ol>') === 9) {
                return '<ol class="inserted">' . mb_substr($str, 4);
            } elseif (mb_stripos($str, '<ul>')) {
                return '<li class="inserted">' . mb_substr($str, 12);
            } elseif (mb_stripos($str, '<blockquote>')) {
                return '<blockquote class="inserted">' . $str;
            } else {
                return '<ins>' . $str . '</ins>';
            }
        }
    }

    /**
     * @param string $str
     * @return string
     */
    private function wrapWithDelete($str)
    {
        if ($str == '') {
            return '';
        } elseif (preg_match('/^<[^>]*>$/siu', $str)) {
            return $str;
            /*
        } elseif ($str == static::ORIG_LINEBREAK) {
            return $str;
            */
        }
        if ($this->formatting == static::FORMATTING_INLINE) {
            if (mb_stripos($str, '<ul>') === 0) {
                return '<div style="color: red; margin: 0; padding: 0;"><ul class="deleted">' .
                mb_substr($str, 4) . '</div>';
            } elseif (mb_stripos($str, '<ol>') === 9) {
                return '<div style="color: red; margin: 0; padding: 0;"><ol class="deleted">' .
                mb_substr($str, 4) . '</div>';
            } elseif (mb_stripos($str, '<ul>')) {
                return '<div style="color: red; margin: 0; padding: 0;"><li class="deleted">' .
                mb_substr($str, 12) . '</div>';
            } elseif (mb_stripos($str, '<blockquote>')) {
                return '<div style="color: red; margin: 0; padding: 0;"><blockquote class="deleted">' .
                $str . '</div>';
            } else {
                return '<span style="color: red;"><del>' . $str . '</del></span>';
            }
        } else {
            if (mb_stripos($str, '<ul>') === 0) {
                return '<ul class="deleted">' . mb_substr($str, 4);
            } elseif (mb_stripos($str, '<ol>') === 9) {
                return '<ol class="deleted">' . mb_substr($str, 4);
            } elseif (mb_stripos($str, '<ul>')) {
                return '<li class="deleted">' . mb_substr($str, 12);
            } elseif (mb_stripos($str, '<blockquote>')) {
                return '<blockquote class="deleted">' . $str;
            } else {
                $str = str_replace('<p>', '<del><p>', $str);
                $str = str_replace('</p>', '</p></del>', $str);
                $str = '<del>' . $str . '</del>';
                $str = str_replace('</del></del>', '</del>', $str);
                $str = str_replace('<del><del>', '<del>', $str);
                return $str;
            }
        }
    }

    /**
     * @param string $line
     * @return string[]
     */
    public static function tokenizeLine($line)
    {
        $line = str_replace(" ", " \n", $line);
        $line = str_replace("<", "\n<", $line);
        $line = str_replace(">", ">\n", $line);
        $line = str_replace("-", "-\n", $line);
        return $line;
    }

    /**
     * @param string $line
     * @return string
     */
    public static function untokenizeLine($line)
    {
        $line = str_replace("\n", '', $line);
        return $line;
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
        $currentSpool = [];
        foreach ($operations as $operation) {
            $firstfour = mb_substr($operation[0], 0, 4);
            if ($operation[0] == static::ORIG_LINEBREAK || preg_match('/^<[^>]*>$/siu', $operation[0])) {
                if (count($currentSpool) > 0) {
                    $return[] = [
                        implode($groupBy, $currentSpool),
                        $preOp
                    ];
                }
                $return[]     = [
                    $operation[0],
                    $operation[1],
                ];
                $preOp        = null;
                $currentSpool = [];
            } elseif ($operation[1] !== $preOp || $firstfour == '<ul>' || $firstfour == '<ol>') {
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
     * @param string $wordDel
     * @param string $wordInsert
     * @return string
     */
    private function computeWordDiff($wordDel, $wordInsert)
    {
        $pre     = $this->getCommonPrefix($wordDel, $wordInsert);
        $restDel = mb_substr($wordDel, mb_strlen($pre));
        $restIns = mb_substr($wordInsert, mb_strlen($pre));

        $post    = $this->getCommonSuffix($restDel, $restIns);
        $restDel = mb_substr($restDel, 0, mb_strlen($restDel) - mb_strlen($post));
        $restIns = mb_substr($restIns, 0, mb_strlen($restIns) - mb_strlen($post));

        return $pre . $this->wrapWithDelete($restDel) . $this->wrapWithInsert($restIns) . $post;
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
        $lineOld      = static::tokenizeLine($lineOld);
        $lineNew      = static::tokenizeLine($lineNew);

        $return = $this->engine->compareStrings($lineOld, $lineNew);

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

        $computedStr = implode("\n", $computedStrs);
        if ($this->debug) {
            echo "\n\n---\n";
            var_dump($computedStr);
            echo "\n---\n";
        }

        $combined = static::untokenizeLine($computedStr);
        $combined = str_replace('</del> <del>', ' ', $combined);
        $combined = str_replace('</del><del>', '', $combined);
        $combined = str_replace('</ins> <ins>', ' ', $combined);
        $combined = str_replace('</ins><ins>', '', $combined);

        if ($this->debug) {
            var_dump($combined);
            die();
        }

        return $combined;
    }

    /**
     * @param string[] $deletes
     * @param string[] $inserts
     * @return string[][]
     */
    public static function matchInsertsToDeletesCalcVariants($deletes, $inserts)
    {
        $emptyArray  = function ($num) {
            $arr = [];
            for ($i = 0; $i < $num; $i++) {
                $arr[] = '';
            }
            return $arr;
        };
        $spaceToFill = count($deletes) - count($inserts);

        if ($spaceToFill == 0) {
            return [$inserts];
        }
        if (count($inserts) == 0) {
            return [$emptyArray($spaceToFill)];
        }
        $insVariants = [];
        for ($trailingSpaces = 0; $trailingSpaces <= $spaceToFill; $trailingSpaces++) {
            $tmpInserts = $inserts;
            $tmpDeletes = $deletes;
            $insBegin   = [];
            for ($i = 0; $i < $trailingSpaces; $i++) {
                $insBegin[] = '';
                array_shift($tmpDeletes);
            }
            $insBegin[] = array_shift($tmpInserts);
            array_shift($tmpDeletes);
            $recVariants = static::matchInsertsToDeletesCalcVariants($tmpDeletes, $tmpInserts);
            foreach ($recVariants as $recVariant) {
                $mergedVariant = array_merge($insBegin, $recVariant);
                $insVariants[] = $mergedVariant;
            }
        }
        return $insVariants;
    }

    /**
     * @param string[] $deletes
     * @param string[] $inserts
     * @return int
     */
    public static function matchInsertsBestFitCalSimilarity($deletes, $inserts)
    {
        $similarity = 0;
        for ($i = 0; $i < count($deletes); $i++) {
            $similarity += similar_text($deletes[$i], $inserts[$i]);
        }
        return $similarity;
    }

    /**
     * @param string[] $deletes
     * @param string[][] $insertVariants
     * @return string[]
     */
    public static function matchInsertsBestFit($deletes, $insertVariants)
    {
        $bestVariant           = null;
        $bestVariantSimilarity = 0;
        foreach ($insertVariants as $insertVariant) {
            $similarity = static::matchInsertsBestFitCalSimilarity($deletes, $insertVariant);
            if ($similarity > $bestVariantSimilarity) {
                $bestVariantSimilarity = $similarity;
                $bestVariant           = $insertVariant;
            }
        }
        return $bestVariant;
    }

    /**
     * @param string[] $deletes
     * @param string[] $inserts
     * @return array
     */
    public static function matchInsertsToDeletes($deletes, $inserts)
    {

        $newDeletes     = $deletes;
        $newInserts     = $inserts;
        $insertVariants = static::matchInsertsToDeletesCalcVariants($newDeletes, $newInserts);
        $bestFitInserts = static::matchInsertsBestFit($newDeletes, $insertVariants);
        return [$newDeletes, $bestFitInserts];
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
        list($newDeletes, $newInserts) = static::matchInsertsToDeletes($deleteStrs, $insertStrs);
        return [$newDeletes, $newInserts, count($deleteStrs) + count($insertStrs)];
    }

    /**
     * @param array $diff
     * @return array
     */
    public static function getUnchangedPrefixPostfixArr($diff)
    {
        $prefix      = $postfix = $middle = [];
        $firstChange = $lastChange = null;
        for ($i = 0; $i < count($diff) && $firstChange === null; $i++) {
            if ($diff[$i][1] == Engine::UNMODIFIED) {
                $prefix[] = $diff[$i];
            } else {
                $firstChange = $i;
            }
        }
        if ($firstChange === null) {
            return [$prefix, $middle, $postfix];
        }
        for ($i = count($diff) - 1; $i >= 0 && $lastChange === null; $i--) {
            if ($diff[$i][1] == Engine::UNMODIFIED) {
                $postfix[] = $diff[$i];
            } else {
                $lastChange = $i;
            }
        }
        $postfix = array_reverse($postfix);
        for ($i = $firstChange; $i <= $lastChange; $i++) {
            $middle[] = $diff[$i];
        }
        return [$prefix, $middle, $postfix];
    }

    /**
     * @param string $orig
     * @param string $new
     * @param string $diff
     * @param string $ignoreStr
     * @return string[]
     */
    public static function getUnchangedPrefixPostfix($orig, $new, $diff, $ignoreStr)
    {
        $firstTagOrig = (preg_match('/^<[^>]+>/siu', $orig, $matchesOrig) ? $matchesOrig[0] : '');
        $firstTagNew  = (preg_match('/^<[^>]+>/siu', $new, $matchesNew) ? $matchesNew[0] : '');
        if ($firstTagOrig != $firstTagNew) {
            return ['', $orig, $new, $diff, ''];
        }

        $parts      = preg_split('/<\/?(ins|del)>/siu', $diff);
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
        $prefixNew     = str_replace($ignoreStr, '', $prefix);
        $prefixNewLen  = mb_strlen($prefixNew);
        $postfixLen    = mb_strlen($postfix);
        $postfixNew    = str_replace($ignoreStr, '', $postfix);
        $postfixNewLen = mb_strlen($postfixNew);
        $middleDiff    = mb_substr($diff, $prefixLen, mb_strlen($diff) - $prefixLen - $postfixLen);
        $middleOrig    = mb_substr($orig, $prefixLen, mb_strlen($orig) - $prefixLen - $postfixLen);
        $middleNew     = mb_substr($new, $prefixNewLen, mb_strlen($new) - $prefixNewLen - $postfixNewLen);

        return [$prefix, $middleOrig, $middleNew, $middleDiff, $postfix];
    }

    /**
     * @param string $orig
     * @param string $diff
     * @return float
     */
    public static function computeLineDiffChangeRatio($orig, $diff)
    {
        $origLength = mb_strlen(strip_tags($orig));
        if ($origLength == 0) {
            return 0;
        }
        $strippedDiff       = preg_replace('/<ins>(.*)<\/ins>/siuU', '', $diff);
        $strippedDiff       = preg_replace('/<del>(.*)<\/del>/siuU', '', $strippedDiff);
        $strippedDiffLength = mb_strlen(strip_tags($strippedDiff));

        return 1.0 - ($strippedDiffLength / $origLength);
    }

    /**
     * @param array $diff
     * @return float
     */
    public static function computeArrDiffChangeRatio($diff)
    {
        if (count($diff) == 0) {
            return 0;
        }
        $unchanged = $inserted = $deleted = 0;
        for ($i = 0; $i < count($diff); $i++) {
            if ($diff[$i][1] == Engine::UNMODIFIED) {
                $unchanged++;
            } elseif ($diff[$i][1] == Engine::INSERTED) {
                $inserted++;
            } elseif ($diff[$i][1] == Engine::DELETED) {
                $deleted++;
            }
        }
        return 1.0 - ($unchanged / count($diff));
    }

    /**
     * @param string $strOld
     * @param string $strNew
     * @return string
     * @throws Internal
     */
    public function computeDiff($strOld, $strNew)
    {
        $computedStr = '';

        $return = $this->engine->compareStrings($strOld, $strNew);
        $return = $this->groupOperations($return, static::ORIG_LINEBREAK);

        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStr .= $return[$i][0] . "\n";
            } elseif ($return[$i][1] == Engine::DELETED) {
                $updates = $this->computeSubsequentInsertsDeletes($return, $i);
                if ($updates) {
                    list ($deletes, $inserts, $count) = $updates;
                    for ($j = 0; $j < count($deletes); $j++) {
                        $del       = $deletes[$j];
                        $ins       = $inserts[$j];
                        $lineDiff  = $this->computeLineDiff($del, $ins);
                        $ignoreStr = $this->engine->getIgnoreStr();
                        $split     = $this->getUnchangedPrefixPostfix($del, $ins, $lineDiff, $ignoreStr);
                        list($prefix, $middleOrig, $middleNew, $middleDiff, $postfix) = $split;

                        if (mb_strlen($middleOrig) > static::MAX_LINE_CHANGE_RATIO_MIN_LEN) {
                            $changeRatio = $this->computeLineDiffChangeRatio($middleOrig, $middleDiff);
                            if ($changeRatio > static::MAX_LINE_CHANGE_RATIO) {
                                $computedStr .= $prefix;
                                $computedStr .= $this->wrapWithDelete($middleOrig);
                                $insertStr = $this->wrapWithInsert($middleNew);
                                if ($insertStr != '') {
                                    $computedStr .= "\n" . $insertStr;
                                }
                                $computedStr .= $postfix . "\n";
                            } else {
                                $computedStr .= $prefix;
                                $computedStr .= $middleDiff;
                                $computedStr .= $postfix . "\n";
                            }
                        } else {
                            $computedStr .= $lineDiff . "\n";
                        }
                    }
                    $i += $count - 1;
                } else {
                    $computedStr .= $this->wrapWithDelete($return[$i][0]) . "\n";
                }
            } elseif ($return[$i][1] == Engine::INSERTED) {
                $computedStr .= $this->wrapWithInsert($return[$i][0]) . "\n";
            } else {
                throw new Internal('Unknown type: ' . $return[$i][1]);
            }
        }
        $force = '###FORCELINEBREAK###';

        $computedStr = str_replace($force . ' ' . static::ORIG_LINEBREAK, $force, $computedStr);
        $computedStr = str_replace(static::ORIG_LINEBREAK, "\n", $computedStr);

        return trim($computedStr);
    }

    /**
     * @param string $str1
     * @param string $str2
     * @return string
     */
    public static function getCommonBeginning($str1, $str2)
    {
        $common = '';
        for ($i = 0; $i < mb_strlen($str1) && $i < mb_strlen($str2); $i++) {
            if (mb_substr($str1, $i, 1) == mb_substr($str2, $i, 1)) {
                $common .= mb_substr($str1, $i, 1);
            } else {
                return $common;
            }
        }
        return $common;
    }

    /**
     * @param string[] $origParagraphs
     * @param string[] $amendParagraphs
     * @return string[]
     * @throws Internal
     */
    public function computeAmendmentAffectedParagraphs($origParagraphs, $amendParagraphs)
    {
        $diffEng = new Engine();
        $diff    = $diffEng->compareArrays($origParagraphs, $amendParagraphs);

        $currOrigPara  = 0;
        $pendingInsert = '';
        /** @var ParagraphAmendment[] $changed */
        $changed = [];
        /** @var string[] $unchanged */
        $unchanged = [];

        for ($currDiffLine = 0; $currDiffLine < count($diff); $currDiffLine++) {
            $diffLine = $diff[$currDiffLine];
            if ($diffLine[1] == Engine::UNMODIFIED) {
                if ($pendingInsert != '') {
                    $changed[$currOrigPara] = $pendingInsert . $diffLine[0];
                    $pendingInsert          = '';
                } else {
                    $unchanged[$currOrigPara] = $diffLine[0];
                }
                $currOrigPara++;
                continue;
            }
            if ($diffLine[1] == Engine::INSERTED) {
                $insertStr = $diffLine[0];
                if ($currOrigPara > 0) {
                    $prevLine = $currOrigPara - 1;
                    if (isset($changed[$prevLine])) {
                        $changed[$prevLine] = $changed[$prevLine] . $insertStr;
                    } else {
                        $changed[$prevLine] = $unchanged[$prevLine] . $insertStr;
                    }
                } else {
                    $pendingInsert .= $insertStr;
                }
                continue;
            }
            if ($diffLine[1] == Engine::DELETED) {
                if ($pendingInsert) {
                    throw new Internal('Not implemented yet - does this even happen?');
                }

                $updates = $this->computeSubsequentInsertsDeletes($diff, $currDiffLine);
                if ($updates) {
                    for ($j = 0; $j < count($updates[0]); $j++) {
                        $changed[$currOrigPara] = $updates[1][$j];
                        $currOrigPara++;
                    }
                    $currDiffLine += $updates[2] - 1;
                } else {
                    $changed[$currOrigPara] = '';
                    $currOrigPara++;
                }

                continue;
            }
        }

        return $changed;
    }

    /**
     * @param string[] $origParagraphs
     * @param string[] $amParas
     * @param int $currOrigLine
     * @param int $lineLength
     * @param AmendmentSection|null $amSec
     * @return \app\models\db\MotionSectionParagraphAmendment[]
     * @throws Internal
     */
    public function computeAmendmentParagraphDiffInt($origParagraphs, $amParas, $currOrigLine, $lineLength, $amSec)
    {
        $diffEng      = new Engine();
        $diff         = $diffEng->compareArrays($origParagraphs, $amParas);
        $currOrigPara = 0;

        $pendingInsert = '';
        /** @var ParagraphAmendment[] $changed */
        $changed = [];
        /** @var string[] $unchanged */
        $unchanged = [];

        for ($currDiffLine = 0; $currDiffLine < count($diff); $currDiffLine++) {
            $diffLine     = $diff[$currDiffLine];
            $firstAffLine = $currOrigLine;
            if ($diffLine[1] == Engine::UNMODIFIED) {
                if ($pendingInsert != '') {
                    $str                    = $pendingInsert . $diffLine[0];
                    $changed[$currOrigPara] = new ParagraphAmendment($amSec, $currOrigPara, $str, $firstAffLine);
                    $pendingInsert          = '';
                } else {
                    $unchanged[$currOrigPara] = $diffLine[0];
                }
                $currOrigPara++;
                $currOrigLine += LineSplitter::countMotionParaLines($diffLine[0], $lineLength);
                continue;
            }
            if ($diffLine[1] == Engine::INSERTED) {
                $insertStr = $this->wrapWithInsert($diffLine[0]);
                if ($currOrigPara > 0) {
                    $prevLine = $currOrigPara - 1;
                    if (isset($changed[$prevLine])) {
                        $changed[$prevLine]->strDiff .= $insertStr;
                    } else {
                        if (!isset($unchanged[$prevLine])) {
                            throw new Internal('unchanged[' . $prevLine . '] not set');
                        }
                        $newStr             = $unchanged[$prevLine] . $insertStr;
                        $changed[$prevLine] = new ParagraphAmendment($amSec, $prevLine, $newStr, $firstAffLine - 1);
                    }
                } else {
                    $pendingInsert .= $insertStr;
                }
                continue;
            }
            if ($diffLine[1] == Engine::DELETED) {
                if ($pendingInsert) {
                    throw new Internal('Not implemented yet - does this even happen?');
                }

                $updates = $this->computeSubsequentInsertsDeletes($diff, $currDiffLine);
                if ($updates) {
                    list ($deletes, $inserts, $count) = $updates;
                    $motionParaLines = 0;
                    for ($j = 0; $j < count($deletes); $j++) {
                        $ins       = $inserts[$j];
                        $del       = $deletes[$j];
                        $lineDiff  = $this->computeLineDiff($del, $ins);
                        $ignoreStr = $this->engine->getIgnoreStr();
                        $split     = $this->getUnchangedPrefixPostfix($del, $ins, $lineDiff, $ignoreStr);
                        list($prefix, $middleOrig, $middleNew, $middleDiff, $postfix) = $split;
                        $motionParaLines += LineSplitter::countMotionParaLines($prefix, $lineLength);

                        if (mb_strlen($middleOrig) > static::MAX_LINE_CHANGE_RATIO_MIN_LEN) {
                            $changeRatio = $this->computeLineDiffChangeRatio($middleOrig, $middleDiff);
                            $changeStr   = $prefix;
                            if ($changeRatio <= static::MAX_LINE_CHANGE_RATIO) {
                                $changeStr .= $middleDiff;
                            } else {
                                $changeStr .= $this->wrapWithDelete($middleOrig) . "\n";
                                $changeStr .= $this->wrapWithInsert($middleNew);
                            }
                            $changeStr .= $postfix . "\n";
                        } else {
                            $changeStr = $lineDiff . "\n";
                        }

                        $currLine         = $firstAffLine + $motionParaLines - 1 + $j;
                        $paraNo           = $currOrigPara + $j;
                        $changed[$paraNo] = new ParagraphAmendment($amSec, $paraNo, $changeStr, $currLine);
                    }
                    $currDiffLine += $count - 1;
                    $currOrigPara += count($deletes);
                } else {
                    $deleteStr              = $this->wrapWithDelete($diffLine[0]);
                    $changed[$currOrigPara] = new ParagraphAmendment($amSec, $currOrigPara, $deleteStr, $firstAffLine);
                    $currOrigPara++;
                }

                $currOrigLine += LineSplitter::countMotionParaLines($diffLine[0], $lineLength);
                continue;
            }
        }
        return $changed;
    }

    /**
     * @param string[] $origParagraphs
     * @param AmendmentSection $amSec
     * @return ParagraphAmendment[]
     * @throws Internal
     */
    public function computeAmendmentParagraphDiff($origParagraphs, AmendmentSection $amSec)
    {
        $amParas      = HTMLTools::sectionSimpleHTML($amSec->data);
        $currOrigLine = $amSec->getFirstLineNumber();
        $lineLength   = $amSec->amendment->motion->motionType->consultation->getSettings()->lineLength;
        return $this->computeAmendmentParagraphDiffInt($origParagraphs, $amParas, $currOrigLine, $lineLength, $amSec);
    }

    /**
     * @param string $html
     * @return string
     */
    public function cleanupDiffProblems($html)
    {
        $ignore = $this->engine->getIgnoreStr();
        if ($ignore) {
            $html = str_replace('<del>' . $ignore, $ignore . '<del>', $html);
            $html = str_replace('<ins>' . $ignore, $ignore . '<ins>', $html);
        }
        $html = str_replace('<ins> </ins></p>', '</p>', $html);
        $html = str_replace('<ins><p>', '<p><ins>', $html);
        $html = str_replace('<ins></p>', '</p><ins>', $html);
        $html = str_replace('<del><p>', '<p><del>', $html);
        $html = str_replace('<dcel></p>', '</p><ins>', $html);
        $html = str_replace('<p></ins>', '</ins><p>', $html);
        $html = str_replace('</p></ins>', '</ins></p>', $html);
        $html = str_replace('</p></del>', '</del></p>', $html);
        $html = str_replace('<ins></ins>', '', $html);
        $html = str_replace('<del></del>', '', $html);

        return $html;
    }
}
