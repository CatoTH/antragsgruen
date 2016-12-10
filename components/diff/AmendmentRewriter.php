<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\exceptions\Internal;

class AmendmentRewriter
{
    /**
     * @param string[]   $oldParagraphs
     * @param string[]   $newParagraphs
     * @return string[]
     * @throws Internal
     */
    private static function computeMatchingAffectedParagraphs($oldParagraphs, $newParagraphs)
    {
        $matcher = new ArrayMatcher();
        list($oldAdjusted, $newAdjusted) = $matcher->matchForDiff($oldParagraphs, $newParagraphs);
        if (count($oldAdjusted) != count($newAdjusted)) {
            throw new Internal('compareSectionedHtml: number of sections does not match');
        }

        $pendinginsert   = '';
        $oldWithoutEmpty = $newWithoutEmpty = [];
        for ($i = 0; $i < count($oldAdjusted); $i++) {
            if ($oldAdjusted[$i] == '###EMPTYINSERTED###') {
                if ($i == 0) {
                    $pendinginsert = $newAdjusted[$i];
                } else {
                    $newWithoutEmpty[$i - 1] .= $newAdjusted[$i];
                }
            } else {
                $oldWithoutEmpty[] = $oldAdjusted[$i];
                $newWithoutEmpty[] = $pendinginsert . $newAdjusted[$i];
                $pendinginsert     = '';
            }
        }

        if (serialize($oldParagraphs) != serialize($oldWithoutEmpty)) {
            throw new Internal("An internal error matching the paragraphs ocurred");
        }

        return $newWithoutEmpty;
    }

    /**
     * @param string[] $oldParagraphs
     * @param string[] $newParagraphs
     * @param bool $asDiff
     * @return \string[]
     * @throws Internal
     */
    private static function computeAffectedParagraphs($oldParagraphs, $newParagraphs, $asDiff = false)
    {
        $matchingNewParagraphs = static::computeMatchingAffectedParagraphs($oldParagraphs, $newParagraphs);

        $affected = [];
        $diff     = new Diff();
        for ($i = 0; $i < count($matchingNewParagraphs); $i++) {
            if ($oldParagraphs[$i] != $matchingNewParagraphs[$i]) {
                if ($asDiff) {
                    $affected[$i] = $diff->computeLineDiff($oldParagraphs[$i], $matchingNewParagraphs[$i]);
                } else {
                    $affected[$i] = $matchingNewParagraphs[$i];
                }
            }
        }

        return $affected;
    }

    /**
     * @param string $motionOldHtml
     * @param string $motionNewHtml
     * @param string $amendmentHtml
     * @param string[] $overrides
     * @return bool
     */
    public static function canRewrite($motionOldHtml, $motionNewHtml, $amendmentHtml, $overrides = [])
    {
        $motionOldSections = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewSections = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentSections = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldSections, $amendmentSections, false);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldSections, $motionNewSections, false);

        $colliding = array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment));
        foreach ($colliding as $col) {
            if (!isset($overrides[$col])) {
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
     * @return string[]
     */
    public static function getCollidingParagraphs($motionOldHtml, $motionNewHtml, $amendmentHtml, $asDiff = false)
    {
        $motionOldSections = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewSections = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentSections = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldSections, $amendmentSections, $asDiff);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldSections, $motionNewSections, $asDiff);

        $paraNos = array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment));
        $paras   = [];
        foreach ($paraNos as $paraNo) {
            $paras[$paraNo] = $affectedByAmendment[$paraNo];
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
        $motionOldSections = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewSections = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentSections = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldSections, $amendmentSections, false);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldSections, $motionNewSections, false);

        $newVersion = [];
        for ($paragraphNo = 0; $paragraphNo < count($motionOldSections); $paragraphNo++) {
            if (isset($overrides[$paragraphNo])) {
                $newVersion[$paragraphNo] = $overrides[$paragraphNo];
            } elseif (isset($affectedByAmendment[$paragraphNo]) && isset($affectedByNewMotion[$paragraphNo])) {
                throw new Internal('Not supported yet');
            } elseif (isset($affectedByAmendment[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByAmendment[$paragraphNo];
            } elseif (isset($affectedByNewMotion[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByNewMotion[$paragraphNo];
            } else {
                $newVersion[$paragraphNo] = $motionOldSections[$paragraphNo];
            }
        }

        return implode("\n", $newVersion);
    }
}
