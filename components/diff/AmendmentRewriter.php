<?php

namespace app\components\diff;

use app\components\HTMLTools;
use app\models\exceptions\Internal;

class AmendmentRewriter
{
    /**
     * @param string[] $oldParagraphs
     * @param string[] $newParagraphs
     * @return string[]
     * @throws Internal
     */
    private static function computeAffectedParagraphs($oldParagraphs, $newParagraphs)
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

        $affected = [];
        for ($i = 0; $i < count($oldWithoutEmpty); $i++) {
            if ($oldWithoutEmpty[$i] != $newWithoutEmpty[$i]) {
                $affected[$i] = $newWithoutEmpty[$i];
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

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldSections, $amendmentSections);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldSections, $motionNewSections);

        if (count(array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment))) == 0) {
            return true;
        }

        return false;
    }

    /**
     * @param string $motionOldHtml
     * @param string $motionNewHtml
     * @param string $amendmentHtml
     * @param string[] $overrides
     * @return string[]
     */
    public static function getCollidingParagraphs($motionOldHtml, $motionNewHtml, $amendmentHtml, $overrides = [])
    {
        $motionOldSections = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewSections = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentSections = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldSections, $amendmentSections);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldSections, $motionNewSections);

        $paraNos = array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment));
        $paras   = [];
        $diff    = new Diff();
        foreach ($paraNos as $paraNo) {
            $paras[] = $diff->computeLineDiff($motionOldSections[$paraNo], $amendmentSections[$paraNo]);
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

        $affectedByAmendment = static::computeAffectedParagraphs($motionOldSections, $amendmentSections);
        $affectedByNewMotion = static::computeAffectedParagraphs($motionOldSections, $motionNewSections);

        $newVersion = [];
        for ($paragraphNo = 0; $paragraphNo < count($motionOldSections); $paragraphNo++) {
            if (isset($affectedByAmendment[$paragraphNo]) && isset($affectedByNewMotion[$paragraphNo])) {
                throw new Internal('Not supported yet');
            } elseif (isset($affectedByAmendment[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByAmendment[$paragraphNo];
            } elseif (isset($affectedByNewMotion[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByNewMotion[$paragraphNo];
            } else {
                $newVersion[$paragraphNo] = $motionOldHtml[$paragraphNo];
            }
        }

        return implode("\n", $newVersion);
    }
}
