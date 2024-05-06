<?php

namespace app\components\diff;

use app\components\diff\DataTypes\DiffWord;
use app\components\HTMLTools;
use app\models\exceptions\Internal;
use app\models\SectionedParagraph;

class AmendmentRewriter
{

    /**
     * @param SectionedParagraph[] $oldParagraphs
     * @param SectionedParagraph[] $newParagraphs
     * @return string[]
     * @throws Internal
     */
    public static function computeAffectedParagraphs(array $oldParagraphs, array $newParagraphs, bool $asDiff = false): array
    {
        $matchingNewParagraphs = ArrayMatcher::computeMatchingAffectedParagraphs($oldParagraphs, $newParagraphs);

        $affected     = [];
        $diff         = new Diff();
        $diffRenderer = new DiffRenderer();
        for ($i = 0; $i < count($matchingNewParagraphs); $i++) {
            if ($oldParagraphs[$i]->html !== $matchingNewParagraphs[$i]) {
                if ($asDiff) {
                    $diffPlain    = $diff->computeLineDiff($oldParagraphs[$i]->html, $matchingNewParagraphs[$i]);
                    $affected[$i] = $diffRenderer->renderHtmlWithPlaceholders($diffPlain);
                } else {
                    $affected[$i] = $matchingNewParagraphs[$i];
                }
            }
        }

        return $affected;
    }

    /**
     * AmendmentDiffMerger::moveInsertsIntoTheirOwnWords does about the same and should behave similarly
     *
     * @param DiffWord[][] $toCheckArr
     * @param DiffWord[][] $refArr
     */
    private static function moveInsertsIntoTheirOwnWords(array $toCheckArr, array $refArr): array
    {
        $insertArr              = function ($arr, $pos, $insertedEl) {
            return array_merge(array_slice($arr, 0, $pos + 1), [$insertedEl], array_slice($arr, $pos + 1));
        };

        $words = count($toCheckArr[0]);
        for ($i = 0; $i < $words; $i++) {
            $word  = $toCheckArr[0][$i];
            $split = explode('###INS_START###', $word->diff);
            if (count($split) === 2 && $split[0] == $word->word) {
                $insEl = new DiffWord();
                $insEl->diff = '###INS_START###' . $split[1];

                $toCheckArr[0] = $insertArr($toCheckArr[0], $i, $insEl);
                $toCheckArr[0][$i]->diff = $split[0];
                $refArr[0] = $insertArr($refArr[0], $i, new DiffWord());
                $i++;
                $words++;
            }
        }
        return [$toCheckArr, $refArr];
    }

    /**
     * @throws Internal
     */
    public static function createMerge(string $motionOldPara, string $motionNewPara, string $amendmentPara): string
    {
        $diff           = new Diff();
        $wordsNewMotion = $diff->compareHtmlParagraphsToWordArray([$motionOldPara], [$motionNewPara]);
        $wordsAmendment = $diff->compareHtmlParagraphsToWordArray([$motionOldPara], [$amendmentPara]);

        if (count($wordsNewMotion) !== count($wordsAmendment)) {
            throw new Internal('canRewrite: word arrays are inconsistent');
        }

        $inDiffMotion = $inDiffAmendment = false;
        $new = [];

        list($wordsNewMotion, $wordsAmendment) = self::moveInsertsIntoTheirOwnWords($wordsNewMotion, $wordsAmendment);
        list($wordsAmendment, $wordsNewMotion) = self::moveInsertsIntoTheirOwnWords($wordsAmendment, $wordsNewMotion);

        for ($i = 0; $i < count($wordsNewMotion[0]); $i++) {
            $wordNewMotion = $wordsNewMotion[0][$i]->diff;
            $wordAmendment = $wordsAmendment[0][$i]->diff;

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
                $new[] = $wordsNewMotion[0][$i]->word;
            }
        }

        $newHtml  = implode('', $new);
        $renderer = new DiffRenderer();
        $newHtml  = $renderer->renderHtmlWithPlaceholders($newHtml);
        return HTMLTools::stripInsDelMarkers($newHtml);
    }

    /**
     * @param string[] $overrides
     * @throws Internal
     */
    public static function canRewrite(string $motionOldHtml, string $motionNewHtml, string $amendmentHtml, array $overrides = []): bool
    {
        $motionOldParas = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewParas = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = self::computeAffectedParagraphs($motionOldParas, $amendmentParas, false);
        $affectedByNewMotion = self::computeAffectedParagraphs($motionOldParas, $motionNewParas, false);

        $colliding = array_intersect(array_keys($affectedByNewMotion), array_keys($affectedByAmendment));
        foreach ($colliding as $col) {
            if (isset($overrides[$col])) {
                continue;
            }
            try {
                self::createMerge($motionOldParas[$col]->html, $affectedByNewMotion[$col], $affectedByAmendment[$col]);
            } catch (\Exception $e) {
                //var_dump($e->getMessage());
                //die();
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $overwrites
     */
    public static function calcNewSectionTextWithOverwrites(string $motionOldHtml, string $amendmentHtml, array $overwrites): string
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
     * @param null|int[] $lineNumbers
     * @return array<array{text: string, amendmentDiff: string, motionNewDiff: string, lineFrom?: int, lineTo?: int}>
     */
    public static function getCollidingParagraphs(
        string $motionOldHtml,
        string $motionNewHtml,
        string $amendmentHtml,
        bool $asDiff = false,
        ?array $lineNumbers = null,
        bool $debug = false
    ): array
    {
        $motionOldParas = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewParas = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = self::computeAffectedParagraphs($motionOldParas, $amendmentParas, $asDiff);
        $affectedByNewMotion = self::computeAffectedParagraphs($motionOldParas, $motionNewParas, $asDiff);

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
                $merge = self::createMerge(
                    $motionOldParas[$paraNo]->html,
                    $affectedByNewMotion[$paraNo],
                    $affectedByAmendment[$paraNo]
                );
                if ($debug) {
                    var_dump($merge);
                }
            } catch (\Exception $e) {
                if ($debug) {
                    echo "COLLISION\n";
                }
                $motionNewDiff = $diff->computeLineDiff($motionOldParas[$paraNo]->html, $affectedByNewMotion[$paraNo]);
                $motionNewDiff = $renderer->renderHtmlWithPlaceholders($motionNewDiff);
                $amendmentDiff = $diff->computeLineDiff($motionOldParas[$paraNo]->html, $affectedByAmendment[$paraNo]);
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
     * @param string[] $overrides
     * @throws Internal
     */
    public static function performRewrite(string $motionOldHtml, string $motionNewHtml, string $amendmentHtml, array $overrides = []): string
    {
        $motionOldParas = HTMLTools::sectionSimpleHTML($motionOldHtml);
        $motionNewParas = HTMLTools::sectionSimpleHTML($motionNewHtml);
        $amendmentParas = HTMLTools::sectionSimpleHTML($amendmentHtml);

        $affectedByAmendment = self::computeAffectedParagraphs($motionOldParas, $amendmentParas, false);
        $affectedByNewMotion = self::computeAffectedParagraphs($motionOldParas, $motionNewParas, false);

        $newVersion = [];
        for ($paragraphNo = 0; $paragraphNo < count($motionOldParas); $paragraphNo++) {
            if (isset($overrides[$paragraphNo])) {
                $newVersion[$paragraphNo] = $overrides[$paragraphNo];
            } elseif (isset($affectedByAmendment[$paragraphNo]) && isset($affectedByNewMotion[$paragraphNo])) {
                $newVersion[$paragraphNo] = self::createMerge(
                    $motionOldParas[$paragraphNo]->html,
                    $affectedByNewMotion[$paragraphNo],
                    $affectedByAmendment[$paragraphNo]
                );

            } elseif (isset($affectedByAmendment[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByAmendment[$paragraphNo];
            } elseif (isset($affectedByNewMotion[$paragraphNo])) {
                $newVersion[$paragraphNo] = $affectedByNewMotion[$paragraphNo];
            } else {
                $newVersion[$paragraphNo] = $motionOldParas[$paragraphNo]->html;
            }
        }

        $new = implode("\n", $newVersion);
        return HTMLTools::removeSectioningFragments($new);
    }
}
