<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\exceptions\Internal;

class AmendmentRewriter
{

    /**
     * @param string[] $oldParagraphs
     * @param string[] $newParagraphs
     * @param bool $asDiff
     * @return \string[]
     * @throws Internal
     */
    public static function computeAffectedParagraphs($oldParagraphs, $newParagraphs, $asDiff = false)
    {
        $matchingNewParagraphs = ArrayMatcher::computeMatchingAffectedParagraphs($oldParagraphs, $newParagraphs);

        $affected     = [];
        $diff         = new Diff();
        $diffRenderer = new DiffRenderer();
        for ($i = 0; $i < count($matchingNewParagraphs); $i++) {
            if ($oldParagraphs[$i] != $matchingNewParagraphs[$i]) {
                if ($asDiff) {
                    $diffPlain    = $diff->computeLineDiff($oldParagraphs[$i], $matchingNewParagraphs[$i]);
                    $affected[$i] = $diffRenderer->renderHtmlWithPlaceholders($diffPlain);
                } else {
                    $affected[$i] = $matchingNewParagraphs[$i];
                }
            }
        }

        return $affected;
    }

    /**
     * AmendmentDiffMerger::moveInsertsIntoTheirOwnWords does about the same and should behave similarily
     *
     * @param array $toCheckArr
     * @param array $refArr
     * @return array
     */
    private static function moveInsertsIntoTheirOwnWords($toCheckArr, $refArr)
    {
        $insertArr              = function ($arr, $pos, $insertedEl) {
            return array_merge(array_slice($arr, 0, $pos + 1), [$insertedEl], array_slice($arr, $pos + 1));
        };

        $words = count($toCheckArr[0]);
        for ($i = 0; $i < $words; $i++) {
            $word  = $toCheckArr[0][$i];
            $split = explode('###INS_START###', $word['diff']);
            if (count($split) === 2 && $split[0] == $word['word']) {
                $insEl = ['word' => '', 'diff' => '###INS_START###' . $split[1]];
                $toCheckArr[0] = $insertArr($toCheckArr[0], $i, $insEl);
                $toCheckArr[0][$i]['diff'] = $split[0];
                $refArr[0] = $insertArr($refArr[0], $i, ['word' => '', 'diff' => '']);
                $i++;
                $words++;
            }
        }
        return [$toCheckArr, $refArr];
    }

    /**
     * @param string $motionOldPara
     * @param string $motionNewPara
     * @param string $amendmentPara
     * @return string
     * @throws Internal
     */
    public static function createMerge($motionOldPara, $motionNewPara, $amendmentPara)
    {
        $diff           = new Diff();
        $wordsNewMotion = $diff->compareHtmlParagraphsToWordArray([$motionOldPara], [$motionNewPara], []);
        $wordsAmendment = $diff->compareHtmlParagraphsToWordArray([$motionOldPara], [$amendmentPara], []);

        if (count($wordsNewMotion) != count($wordsAmendment)) {
            throw new Internal('canRewrite: word arrays are inconsistent');
        }

        $inDiffMotion = $inDiffAmendment = false;
        $new          = [];

        list($wordsNewMotion, $wordsAmendment) = static::moveInsertsIntoTheirOwnWords($wordsNewMotion, $wordsAmendment);
        list($wordsAmendment, $wordsNewMotion) = static::moveInsertsIntoTheirOwnWords($wordsAmendment, $wordsNewMotion);

        for ($i = 0; $i < count($wordsNewMotion[0]); $i++) {
            $wordNewMotion = $wordsNewMotion[0][$i]['diff'];
            $wordAmendment = $wordsAmendment[0][$i]['diff'];

            $hadDiff = false;
            if ($inDiffMotion && $inDiffAmendment && $wordNewMotion != $wordAmendment) {
                throw new Internal('In Diff: ' . $wordNewMotion . ' != ' . $wordAmendment);
            }

            preg_match_all("/###(INS|DEL)_(?<mode>START|END)###/siu", $wordNewMotion, $matchesMotion);
            preg_match_all("/###(INS|DEL)_(?<mode>START|END)###/siu", $wordAmendment, $matchesAmend);
            if (count($matchesMotion['mode']) > 0) {
                if ($inDiffAmendment && !$inDiffMotion) {
                    throw new Internal('Motion changes within amendment changes: ' . $wordNewMotion);
                }
                $hadDiff      = true;
                $inDiffMotion = ($matchesMotion['mode'][count($matchesMotion['mode']) - 1] == 'START');
            }
            if (count($matchesAmend['mode']) > 0) {
                if (($inDiffMotion || $hadDiff) && ($wordNewMotion != $wordAmendment)) {
                    throw new Internal('Amendment changes within motion changes: ' . $wordAmendment);
                }
                $inDiffAmendment = ($matchesAmend['mode'][count($matchesAmend['mode']) - 1] == 'START');
            }

            if (count($matchesMotion['mode']) > 0) {
                $new[] = $wordNewMotion;
            } elseif (count($matchesAmend['mode']) > 0) {
                $new[] = $wordAmendment;
            } else {
                $new[] = $wordsNewMotion[0][$i]['word'];
            }
        }

        $newHtml  = implode('', $new);
        $renderer = new DiffRenderer();
        $newHtml  = $renderer->renderHtmlWithPlaceholders($newHtml);
        return HTMLTools::stripInsDelMarkers($newHtml);
    }

    /**
     * @param string $motionOldHtml
     * @param string $motionNewHtml
     * @param string $amendmentHtml
     * @param string[] $overrides
     * @return bool
     * @throws Internal
     */
    public static function canRewrite($motionOldHtml, $motionNewHtml, $amendmentHtml, $overrides = [])
    {
        $motionOldParas = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewParas = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldParas, $amendmentParas, false);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldParas, $motionNewParas, false);

        $colliding = array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment));
        foreach ($colliding as $col) {
            if (isset($overrides[$col])) {
                continue;
            }
            try {
                static::createMerge($motionOldParas[$col], $affectedByNewMotion[$col], $affectedByAmendment[$col]);
            } catch (\Exception $e) {
                //var_dump($e->getMessage());
                //die();
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $motionOldHtml
     * @param string $amendmentHtml
     * @param string[] $overwrites
     * @return string
     */
    public static function calcNewSectionTextWithOverwrites($motionOldHtml, $amendmentHtml, $overwrites)
    {
        $motionOldParas        = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $amendmentParas        = HTMLTools::sectionSimpleHTML($amendmentHtml);
        $matchingNewParagraphs = ArrayMatcher::computeMatchingAffectedParagraphs($motionOldParas, $amendmentParas);

        foreach ($overwrites as $paraNo => $para) {
            $matchingNewParagraphs[$paraNo] = $para;
        }

        return implode("\n", $matchingNewParagraphs);
    }

    /**
     * @param string $motionOldHtml
     * @param string $motionNewHtml
     * @param string $amendmentHtml
     * @param boolean $asDiff
     * @param null|int[] $lineNumbers
     * @param bool $debug
     * @return \string[]
     */
    public static function getCollidingParagraphs(
        $motionOldHtml,
        $motionNewHtml,
        $amendmentHtml,
        $asDiff = false,
        $lineNumbers = null,
        $debug = false
    )
    {
        $motionOldParas = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewParas = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldParas, $amendmentParas, $asDiff);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldParas, $motionNewParas, $asDiff);

        if ($debug) {
            echo "====\n";
            var_dump($affectedByNewMotion);
            var_dump($affectedByAmendment);
            echo "====\n";
        }

        $paraNos  = array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment));
        $paras    = [];
        $diff     = new Diff();
        $renderer = new DiffRenderer();
        if ($debug) {
            var_dump($paraNos);
        }
        foreach ($paraNos as $paraNo) {
            try {
                if ($debug) {
                    var_dump($motionOldParas[$paraNo]);
                    var_dump($affectedByNewMotion[$paraNo]);
                    var_dump($affectedByAmendment[$paraNo]);
                }
                $merge = static::createMerge(
                    $motionOldParas[$paraNo],
                    $affectedByNewMotion[$paraNo],
                    $affectedByAmendment[$paraNo]
                );
                if ($debug) {
                    var_dump($merge);
                }
            } catch (\Exception $e) {
                if ($debug) {
                    echo "COLLISSION\n";
                }
                $motionNewDiff = $diff->computeLineDiff($motionOldParas[$paraNo], $affectedByNewMotion[$paraNo]);
                $motionNewDiff = $renderer->renderHtmlWithPlaceholders($motionNewDiff);
                $amendmentDiff = $diff->computeLineDiff($motionOldParas[$paraNo], $affectedByAmendment[$paraNo]);
                $amendmentDiff = $renderer->renderHtmlWithPlaceholders($amendmentDiff);
                $data          = [
                    'text'          => $affectedByAmendment[$paraNo],
                    'amendmentDiff' => $amendmentDiff,
                    'motionNewDiff' => $motionNewDiff,
                ];
                if ($lineNumbers) {
                    $data['lineFrom'] = $lineNumbers[$paraNo];
                    $data['lineTo']   = $lineNumbers[$paraNo + 1] - 1;
                }
                $paras[$paraNo] = $data;
            }
        }

        return $paras;
    }

    /**
     * @param string $motionOldHtml
     * @param string $motionNewHtml
     * @param string $amendmentHtml
     * @param string[] $overrides
     * @return string
     * @throws Internal
     */
    public static function performRewrite($motionOldHtml, $motionNewHtml, $amendmentHtml, $overrides = [])
    {
        $motionOldParas = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewParas = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldParas, $amendmentParas, false);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldParas, $motionNewParas, false);

        $newVersion = [];
        for ($paragraphNo = 0; $paragraphNo < count($motionOldParas); $paragraphNo++) {
            if (isset($overrides[$paragraphNo])) {
                $newVersion[$paragraphNo] = $overrides[$paragraphNo];
            } elseif (isset($affectedByAmendment[$paragraphNo]) && isset($affectedByNewMotion[$paragraphNo])) {
                $newVersion[$paragraphNo] = static::createMerge(
                    $motionOldParas[$paragraphNo],
                    $affectedByNewMotion[$paragraphNo],
                    $affectedByAmendment[$paragraphNo]
                );

            } elseif (isset($affectedByAmendment[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByAmendment[$paragraphNo];
            } elseif (isset($affectedByNewMotion[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByNewMotion[$paragraphNo];
            } else {
                $newVersion[$paragraphNo] = $motionOldParas[$paragraphNo];
            }
        }

        $new = implode("\n", $newVersion);
        return HTMLTools::removeSectioningFragments($new);
    }
}
