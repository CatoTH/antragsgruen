<?php

declare(strict_types=1);

namespace app\components;

use app\models\db\{Amendment, IMotion, Motion};
use app\models\settings\AntragsgruenApp;

class IMotionSorter
{
    public const SORT_STATUS = 1;
    public const SORT_TITLE = 2;
    public const SORT_TITLE_PREFIX = 3;
    public const SORT_INITIATOR = 4;
    public const SORT_TAG = 5;
    public const SORT_PUBLICATION = 6;
    public const SORT_PROPOSAL = 7;
    public const SORT_PROPOSAL_STATUS = 8;
    public const SORT_RESPONSIBILITY = 9;
    public const SORT_DATE = 10;

    /**
     * @param IMotion[] $imotions
     *
     * @return IMotion[]
     */
    public static function sortIMotions(array $imotions, int $sort): array
    {
        foreach (AntragsgruenApp::getActivePlugins() as $plugin) {
            $sorted = $plugin::sortIMotions($imotions, $sort);
            if ($sorted !== null) {
                return $sorted;
            }
        }

        switch ($sort) {
            case self::SORT_TITLE:
                usort($imotions, [IMotionSorter::class, 'sortTitle']);
                break;
            case self::SORT_STATUS:
                usort($imotions, [IMotionSorter::class, 'sortStatus']);
                break;
            case self::SORT_TITLE_PREFIX:
                usort($imotions, [IMotionSorter::class, 'sortTitlePrefix']);
                break;
            case self::SORT_INITIATOR:
                usort($imotions, [IMotionSorter::class, 'sortInitiator']);
                break;
            case self::SORT_TAG:
                usort($imotions, [IMotionSorter::class, 'sortTag']);
                break;
            case self::SORT_PROPOSAL_STATUS:
                usort($imotions, [IMotionSorter::class, 'sortProposalStatus']);
                break;
            case self::SORT_RESPONSIBILITY:
                usort($imotions, [IMotionSorter::class, 'sortResponsibility']);
                break;
            case self::SORT_DATE:
                usort($imotions, [IMotionSorter::class, 'sortDate']);
                break;
            default:
                usort($imotions, [IMotionSorter::class, 'sortTitlePrefix']);
        }
        if (!in_array($sort, [self::SORT_STATUS, self::SORT_INITIATOR, self::SORT_TAG])) {
            $imotions = self::moveAmendmentsToMotions($imotions);
        }

        return $imotions;
    }

    private static function sortStatus(IMotion $motion1, IMotion $motion2): int
    {
        return $motion1->status <=> $motion2->status;
    }

    private static function sortProposalStatus(IMotion $motion1, IMotion $motion2): int
    {
        $status1 = (is_a($motion1, Amendment::class) ? $motion1->proposalStatus : 0);
        $status2 = (is_a($motion2, Amendment::class) ? $motion2->proposalStatus : 0);
        return $status1 <=> $status2;
    }

    private static function normalizeResponsibility(IMotion $motion): string
    {
        $parts = [];
        if ($motion->responsibilityUser) {
            if ($motion->responsibilityUser->name) {
                $parts[] = trim($motion->responsibilityUser->name);
            } else {
                $parts[] = trim($motion->responsibilityUser->getAuthName());
            }
        }
        if ($motion->responsibilityComment) {
            $parts[] = trim($motion->responsibilityComment);
        }

        return trim(implode(' ', $parts));
    }

    private static function sortResponsibility(IMotion $motion1, IMotion $motion2): int
    {
        $responsibility1 = self::normalizeResponsibility($motion1);
        $responsibility2 = self::normalizeResponsibility($motion2);

        if ($responsibility1 && $responsibility2) {
            return strnatcasecmp($responsibility1, $responsibility2);
        } elseif ($responsibility1) {
            return -1;
        } elseif ($responsibility2) {
            return 1;
        } else {
            return 0;
        }
    }

    private static function sortTitle(IMotion $motion1, IMotion $motion2): int
    {
        if (is_a($motion1, Motion::class)) {
            /** @var Motion $motion1 */
            $title1 = $motion1->title;
        } else {
            /** @var Amendment $motion1 */
            $title1 = $motion1->getMyMotion()->title;
        }
        if (is_a($motion2, Motion::class)) {
            /** @var Motion $motion2 */
            $title2 = $motion2->title;
        } else {
            /** @var Amendment $motion2 */
            $title2 = $motion2->getMyMotion()->title;
        }
        $cmp = strnatcasecmp($title1, $title2);
        if ($cmp === 0) {
            return ($motion1->id < $motion2->id ? 1 : -1);
        } else {
            return $cmp;
        }
    }

    private static function sortTitlePrefix(IMotion $motion1, IMotion $motion2): int
    {
        if (is_a($motion1, Motion::class)) {
            /** @var Motion $motion1 */
            $rev1 = $motion1->getFormattedTitlePrefix();
        } else {
            /** @var Amendment $motion1 */
            $rev1 = $motion1->getFormattedTitlePrefix() . ' ' . \Yii::t('amend', 'amend_for_motion') .
                    ' ' . $motion1->getMyMotion()->getFormattedTitlePrefix();
        }
        if (is_a($motion2, Motion::class)) {
            /** @var Motion $motion2 */
            $rev2 = $motion2->getFormattedTitlePrefix();
        } else {
            /** @var Amendment $motion2 */
            $rev2 = $motion2->getFormattedTitlePrefix() . ' ' . \Yii::t('amend', 'amend_for_motion') .
                    ' ' . $motion2->getMyMotion()->getFormattedTitlePrefix();
        }

        return strnatcasecmp($rev1, $rev2);
    }

    private static function sortInitiator(IMotion $motion1, IMotion $motion2): int
    {
        $init1 = $motion1->getInitiatorsStr();
        $init2 = $motion2->getInitiatorsStr();
        $cmp   = strnatcasecmp($init1, $init2);
        if ($cmp === 0) {
            return self::sortTitlePrefix($motion1, $motion2);
        } else {
            return $cmp;
        }
    }

    private static function sortTag(IMotion $motion1, IMotion $motion2): int
    {
        if (is_a($motion1, Motion::class)) {
            /** @var Motion $motion1 */
            if (count($motion1->getPublicTopicTags()) > 0) {
                $tag1 = $motion1->getPublicTopicTags()[0];
            } else {
                $tag1 = null;
            }
        } else {
            $tag1 = null;
        }
        if (is_a($motion2, Motion::class)) {
            /** @var Motion $motion2 */
            if (count($motion2->getPublicTopicTags()) > 0) {
                $tag2 = $motion2->getPublicTopicTags()[0];
            } else {
                $tag2 = null;
            }
        } else {
            $tag2 = null;
        }
        if ($tag1 === null && $tag2 === null) {
            return 0;
        } elseif ($tag1 === null) {
            return 1;
        } elseif ($tag2 === null) {
            return -1;
        } else {
            $cmp = strnatcasecmp($tag1->title, $tag2->title);
            if ($cmp === 0) {
                return self::sortTitlePrefix($motion1, $motion2);
            } else {
                return $cmp;
            }
        }
    }

    private static function sortDate(IMotion $imotion1, IMotion $imotion2): int
    {
        $timestamp1 = ($imotion1->dateCreation ? Tools::dateSql2timestamp($imotion1->dateCreation) : 0);
        $timestamp2 = ($imotion2->dateCreation ? Tools::dateSql2timestamp($imotion2->dateCreation) : 0);
        return $timestamp1 <=> $timestamp2;
    }

    /**
     * @param IMotion[] $entries
     *
     * @return IMotion[]
     */
    private static function moveAmendmentsToMotions(array $entries): array
    {
        $foundMotions = [];
        foreach ($entries as $entry) {
            if (is_a($entry, Motion::class)) {
                $foundMotions[] = $entry->id;
            }
        }
        /** @var IMotion[] $newArr1 */
        $newArr1 = [];
        /** @var Amendment[] $movingAmendments */
        $movingAmendments = [];
        foreach ($entries as $entry) {
            if (is_a($entry, Amendment::class)) {
                /** @var Amendment $entry */
                if (in_array($entry->motionId, $foundMotions)) {
                    $movingAmendments[] = $entry;
                } else {
                    $newArr1[] = $entry;
                }
            } else {
                $newArr1[] = $entry;
            }
        }
        /** @var IMotion[] $result */
        $result = [];
        foreach ($newArr1 as $entry) {
            $result[] = $entry;
            if (is_a($entry, Motion::class)) {
                foreach ($movingAmendments as $amendment) {
                    if ($amendment->motionId === $entry->id) {
                        $result[] = $amendment;
                    }
                }
            }
        }

        return $result;
    }
}
