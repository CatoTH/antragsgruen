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
     * @param string $motionNewHtml
     * @param string $amendmentHtml
     * @param boolean $asDiff
     * @param null|int[] $lineNumbers
     * @return string[]
     */
    public static function getCollidingParagraphs(
        $motionOldHtml,
        $motionNewHtml,
        $amendmentHtml,
        $asDiff = false,
        $lineNumbers = null
    )
    {
        $motionOldParas = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewParas = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldParas, $amendmentParas, $asDiff);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldParas, $motionNewParas, $asDiff);

        $paraNos  = array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment));
        $paras    = [];
        $diff     = new Diff();
        $renderer = new DiffRenderer();
        foreach ($paraNos as $paraNo) {
            try {
                static::createMerge($motionOldParas[$paraNo], $motionNewParas[$paraNo], $amendmentParas[$paraNo]);
            } catch (\Exception $e) {
                $motionNewDiff = $diff->computeLineDiff($motionOldParas[$paraNo], $motionNewParas[$paraNo]);
                $motionNewDiff = $renderer->renderHtmlWithPlaceholders($motionNewDiff);
                $amendmentDiff = $diff->computeLineDiff($motionOldParas[$paraNo], $amendmentParas[$paraNo]);
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

        return implode("\n", $newVersion);
    }
}
