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

    const MAX_LINE_CHANGE_RATIO_MIN_LEN = 500;
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
        } elseif (preg_match('/^<[^>]*>$/siu', $str)) {
            return $str;
        } elseif ($str == static::ORIG_LINEBREAK) {
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
                return '<div style="color: green;">' . $str . '</div>';
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
        } elseif ($str == static::ORIG_LINEBREAK) {
            return $str;
        }
        if ($this->formatting == static::FORMATTING_INLINE) {
            if (mb_stripos($str, '<ul>') === 0) {
                return '<div style="color: red; margin: 0; padding: 0;"><ul class="inserted">' .
                mb_substr($str, 4) . '</div>';
            } elseif (mb_stripos($str, '<ol>') === 9) {
                return '<div style="color: red; margin: 0; padding: 0;"><ol class="inserted">' .
                mb_substr($str, 4) . '</div>';
            } elseif (mb_stripos($str, '<ul>')) {
                return '<div style="color: red; margin: 0; padding: 0;"><li class="inserted">' .
                mb_substr($str, 12) . '</div>';
            } elseif (mb_stripos($str, '<blockquote>')) {
                return '<div style="color: red; margin: 0; padding: 0;"><blockquote class="inserted">' .
                $str . '</div>';
            } else {
                return '<div style="color: red;">' . $str . '</div>';
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
    private function tokenizeLine($line)
    {
        $line = str_replace(" ", " \n", $line);
        $line = str_replace("<", "\n<", $line);
        $line = str_replace(">", ">\n", $line);
        return $line;
    }

    /**
     * @param string $line
     * @return string
     */
    private function untokenizeLine($line)
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
        $lineOld      = $this->tokenizeLine($lineOld);
        $lineNew      = $this->tokenizeLine($lineNew);

        $return = $this->engine->compareStrings($lineOld, $lineNew);
        $return = $this->groupOperations($return, '');

        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStrs[] = $return[$i][0];
            } elseif ($return[$i][1] == Engine::DELETED) {
                if (isset($return[$i + 1]) && $return[$i + 1][1] == Engine::INSERTED) {
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

        $combined = $this->untokenizeLine($computedStr);
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
     * @param array $arr
     * @param int $idx
     * @return array|null
     */
    private function computeSubsequentInsertsDeletes($arr, $idx)
    {
        $numDeletes = 0;
        $deleteStrs = [];
        $insertStrs = [];
        while ($idx < count($arr) && $arr[$idx][1] == Engine::DELETED) {
            $deleteStrs[] = $arr[$idx][0];
            $numDeletes++;
            $idx++;
        }
        for ($i = 0; $i < $numDeletes; $i++) {
            if (!isset($arr[$idx + $i]) || $arr[$idx + $i][1] != Engine::INSERTED) {
                return null;
            }
            $insertStrs[] = $arr[$idx + $i][0];
        }
        return [$deleteStrs, $insertStrs, $numDeletes];
    }

    /**
     * @param string $orig
     * @param string $new
     * @param string $diff
     * @return string[]
     */
    private function getUnchangedPrefixPostfix($orig, $new, $diff)
    {
        $parts      = preg_split('/<\/?(ins|del)>/siu', $diff);
        $prefix     = $parts[0];
        $postfix    = $parts[count($parts) - 1];
        $prefixLen  = mb_strlen($prefix);
        $postfixLen = mb_strlen($postfix);

        if ($prefixLen < 40) {
            $prefix = '';
        } else {
            if ($prefixLen > 40 && mb_strrpos($prefix, '. ') > $prefixLen - 40) {
                $prefix = mb_substr($prefix, 0, mb_strrpos($prefix, '. ') + 2);
            } elseif ($prefixLen > 40 && mb_strrpos($prefix, '.') > $prefixLen - 40) {
                $prefix = mb_substr($prefix, 0, mb_strrpos($prefix, '.') + 1);
            } elseif ($prefixLen > 40 && mb_strrpos($prefix, '.') > $prefixLen - 40) {
                $prefix = mb_substr($prefix, 0, mb_strrpos($prefix, ' ') + 1);
            }
        }
        if ($postfixLen < 40) {
            $postfix = '';
        } else {
            if ($postfixLen > 40 && mb_strpos($postfix, '.') < 40) {
                $postfix = mb_substr($postfix, mb_strpos($postfix, '.') + 1);
            } elseif ($prefixLen > 40 && mb_strrpos($prefix, ' ') > $prefixLen - 40) {
                $postfix = mb_substr($postfix, mb_strpos($postfix, ' ') + 1);
            }
        }

        $prefixLen     = mb_strlen($prefix);
        $prefixNew     = str_replace($this->engine->getIgnoreStr(), '', $prefix);
        $prefixNewLen  = mb_strlen($prefixNew);
        $postfixLen    = mb_strlen($postfix);
        $postfixNew    = str_replace($this->engine->getIgnoreStr(), '', $postfix);
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
    private function computeLineDiffChangeRatio($orig, $diff)
    {
        $origLength = mb_strlen(strip_tags($orig));
        if ($origLength == 0) {
            return 0;
        }
        $strippedDiff = preg_replace('/<ins>(.*)<\/ins>/siuU', '', $diff);
        $strippedDiff = preg_replace('/<del>(.*)<\/del>/siuU', '', $strippedDiff);
        $diffLength   = mb_strlen(strip_tags($strippedDiff));

        return 1.0 - ($diffLength / $origLength);
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

        /*
        echo "\n\n\n";
        var_dump($strOld);
        echo "\n\n";
        var_dump($strNew);
        echo "\n\n";
        var_dump($return);
        */

        $return = $this->groupOperations($return, static::ORIG_LINEBREAK);

        for ($i = 0; $i < count($return); $i++) {
            if ($return[$i][1] == Engine::UNMODIFIED) {
                $computedStr .= $return[$i][0] . "\n";
            } elseif ($return[$i][1] == Engine::DELETED) {
                $updates = $this->computeSubsequentInsertsDeletes($return, $i);
                if ($updates) {
                    list ($deletes, $inserts, $count) = $updates;
                    for ($j = 0; $j < count($deletes); $j++) {
                        $lineDiff = $this->computeLineDiff($deletes[$j], $inserts[$j]);

                        $split = $this->getUnchangedPrefixPostfix($deletes[$j], $inserts[$j], $lineDiff);
                        list($prefix, $middleOrig, $middleNew, $middleDiff, $postfix) = $split;
                        if (mb_strlen($middleOrig) > static::MAX_LINE_CHANGE_RATIO_MIN_LEN) {
                            $changeRatio = $this->computeLineDiffChangeRatio($middleOrig, $middleDiff);
                            $computedStr .= $prefix;
                            if ($changeRatio <= static::MAX_LINE_CHANGE_RATIO) {
                                $computedStr .= $middleDiff;
                            } else {
                                $computedStr .= $this->wrapWithDelete($middleOrig) . "\n";
                                $computedStr .= $this->wrapWithInsert($middleNew);
                            }
                            $computedStr .= $postfix . "\n";
                        } else {
                            $computedStr .= $lineDiff . "\n";
                        }
                    }
                    $i += $count * 2 - 1;
                } else {
                    $computedStr .= $this->wrapWithDelete($return[$i][0]) . "\n";
                }
            } elseif ($return[$i][1] == Engine::INSERTED) {
                $computedStr .= $this->wrapWithInsert($return[$i][0]) . "\n";
            } else {
                throw new Internal('Unknown type: ' . $return[$i][1]);
            }
        }
        $force       = '###FORCELINEBREAK###';
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
     * @param $origParagraphs
     * @param AmendmentSection $amSec
     * @return ParagraphAmendment[]
     * @throws Internal
     */
    public function computeAmendmentParagraphDiff($origParagraphs, AmendmentSection $amSec)
    {
        $amParas       = HTMLTools::sectionSimpleHTML($amSec->data);
        $diffEng       = new Engine();
        $diff          = $diffEng->compareArrays($origParagraphs, $amParas);
        $currOrigPara  = 0;
        $currOrigLine  = $amSec->getFirstLineNumber();
        $lineLength    = $amSec->amendment->motion->motionType->consultation->getSettings()->lineLength;
        $pendingInsert = '';
        /** @var ParagraphAmendment[] $changed */
        $changed = [];

        for ($currDiffLine = 0; $currDiffLine < count($diff); $currDiffLine++) {
            $diffLine     = $diff[$currDiffLine];
            $firstAffLine = $currOrigLine; // @TODO
            if ($diffLine[1] == Engine::UNMODIFIED) {
                if ($pendingInsert != '') {
                    $str                    = $pendingInsert . $diffLine[0];
                    $changed[$currOrigPara] = new ParagraphAmendment($amSec, $currOrigPara, $str, $firstAffLine);
                    $pendingInsert          = '';
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
                        $newStr             = $diff[$prevLine][0] . $insertStr;
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
                    // @todo check if this can happen
                }

                if (isset($diff[$currDiffLine + 1]) && $diff[$currDiffLine + 1][1] == Engine::INSERTED) {
                    $lineDiff = $this->computeLineDiff($diffLine[0], $diff[$currDiffLine + 1][0]);
                    $split = $this->getUnchangedPrefixPostfix($diffLine[0], $diff[$currDiffLine + 1][0], $lineDiff);
                    list($prefix, $middleOrig, $middleNew, $middleDiff, $postfix) = $split;
                    $motionParaLines        = LineSplitter::countMotionParaLines($prefix, $lineLength);

                    if (mb_strlen($middleOrig) > static::MAX_LINE_CHANGE_RATIO_MIN_LEN) {
                        $changeRatio = $this->computeLineDiffChangeRatio($middleOrig, $middleDiff);
                        $changeStr = $prefix;
                        if ($changeRatio <= static::MAX_LINE_CHANGE_RATIO) {
                            $changeStr .= $middleDiff;
                        } else {
                            $changeStr .= $this->wrapWithDelete($middleOrig) . "\n";
                            $changeStr .= $this->wrapWithInsert($middleNew);
                        }
                        $changeStr .= $postfix;
                    } else {
                        $changeStr = $lineDiff;
                    }

                    $currLine               = $firstAffLine + $motionParaLines - 1;
                    $changed[$currOrigPara] = new ParagraphAmendment($amSec, $currOrigPara, $changeStr, $currLine);
                    $currDiffLine++;
                } else {
                    $deleteStr              = $this->wrapWithDelete($diffLine[0]);
                    $changed[$currOrigPara] = new ParagraphAmendment($amSec, $currOrigPara, $deleteStr, $firstAffLine);
                }
                /*
                if (isset($diff[$currDiffLine + 1]) && $diff[$currDiffLine + 1][1] == Engine::INSERTED) {
                    $changeStr              = $this->computeLineDiff($diffLine[0], $diff[$currDiffLine + 1][0]);
                    $commonStr              = static::getCommonBeginning($diffLine[0], $diff[$currDiffLine + 1][0]);
                    $motionParaLines        = LineSplitter::countMotionParaLines($commonStr, $lineLength);
                    $currLine               = $firstAffLine + $motionParaLines - 1;
                    $changed[$currOrigPara] = new ParagraphAmendment($amSec, $currOrigPara, $changeStr, $currLine);
                    $currDiffLine++;
                } else {
                    $deleteStr              = $this->wrapWithDelete($diffLine[0]);
                    $changed[$currOrigPara] = new ParagraphAmendment($amSec, $currOrigPara, $deleteStr, $firstAffLine);
                }
                */
                $currOrigPara++;
                $currOrigLine += LineSplitter::countMotionParaLines($diffLine[0], $lineLength);
                continue;
            }
        }
        return $changed;
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
        $html = str_replace('<del><p>', '<p><del>', $html);
        $html = str_replace('</p></ins>', '</ins></p>', $html);
        $html = str_replace('</p></del>', '</del></p>', $html);
        $html = str_replace('<ins></ins>', '', $html);
        $html = str_replace('<del></del>', '', $html);

        return $html;
    }
}
