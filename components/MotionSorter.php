<?php

namespace app\components;

use app\models\amendmentNumbering\ByLine;
use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, IMotion, Motion, repostory\MotionRepository};

class MotionSorter
{
    protected static function getSortedMotionsSortCmp(string $str1, string $str2, int $num1, int $num2): int
    {
        if ($str1 === $str2) {
            if ($num1 < $num2) {
                $return = -1;
            } elseif ($num1 > $num2) {
                $return = 1;
            } else {
                $return = 0;
            }
        } else {
            $return = strnatcasecmp($str1, $str2);
        }

        return $return;
    }

    public static function stripCommonBeginning(string $str1, string $str2): array
    {
        if ($str1 === '' || $str2 === '') {
            return [$str1, $str2];
        }
        if (is_numeric($str1[0]) && is_numeric($str2[0])) {
            if (intval($str1) === intval($str2) && $str1 != $str2) {
                $str1s = preg_replace('/^[0-9]+[\.\- ]+/', '', $str1);
                $str2s = preg_replace('/^[0-9]+[\.\- ]+/', '', $str2);
                if ($str1s === $str1 || $str2s === $str2) {
                    return [$str1, $str2];
                } else {
                    return static::stripCommonBeginning($str1s, $str2s);
                }
            } else {
                return [$str1, $str2];
            }
        } elseif (!is_numeric($str1[0]) && !is_numeric($str2[0]) && $str1[0] === $str2[0]) {
            return static::stripCommonBeginning(mb_substr($str1, 1), mb_substr($str2, 1));
        } else {
            return [$str1, $str2];
        }
    }

    public static function getSortedMotionsSort(string $prefix1, string $prefix2): int
    {
        if ($prefix1 === '' && $prefix2 === '') {
            return 0;
        }
        if ($prefix1 === '') {
            return -1;
        }
        if ($prefix2 === '') {
            return 1;
        }

        $prefix1 = preg_replace('/neu$/siu', 'neu1', $prefix1);
        $prefix2 = preg_replace('/neu$/siu', 'neu1', $prefix2);
        $prefix1 = preg_replace('/new$/siu', 'new1', $prefix1);
        $prefix2 = preg_replace('/new/siu', 'new1', $prefix2);
        list($prefix1, $prefix2) = static::stripCommonBeginning($prefix1, $prefix2);

        $pat1 = '/^(?<str1>[^0-9]*)(?<num1>[0-9]*)/siu';
        $pat2 = '/^(?<str1>[^0-9]*)(?<num1>[0-9]+)(?<str2>[^0-9]+)(?<num2>[0-9]+)$/siu';

        if (preg_match($pat2, $prefix1, $mat1) && preg_match($pat2, $prefix2, $mat2)) {
            if ($mat1['str1'] === $mat2['str1'] && $mat1['num1'] === $mat2['num1']) {
                return static::getSortedMotionsSortCmp($mat1['str2'], $mat2['str2'], intval($mat1['num2']), intval($mat2['num2']));
            } else {
                return static::getSortedMotionsSortCmp($mat1['str1'], $mat2['str1'], intval($mat1['num1']), intval($mat2['num1']));
            }
        } elseif (preg_match($pat2, $prefix1, $mat1) && preg_match($pat1, $prefix2, $mat2)) {
            if ($mat1['str1'] === $mat2['str1'] && $mat1['num1'] === $mat2['num1']) {
                return 1;
            } else {
                return static::getSortedMotionsSortCmp($mat1['str1'], $mat2['str1'], intval($mat1['num1']), intval($mat2['num1']));
            }
        } elseif (preg_match($pat1, $prefix1, $mat1) && preg_match($pat2, $prefix2, $mat2)) {
            if ($mat1['str1'] === $mat2['str1'] && $mat1['num1'] === $mat2['num1']) {
                return -1;
            } else {
                return static::getSortedMotionsSortCmp($mat1['str1'], $mat2['str1'], intval($mat1['num1']), intval($mat2['num1']));
            }
        } else {
            preg_match($pat1, $prefix1, $mat1);
            preg_match($pat1, $prefix2, $mat2);
            $str1 = $mat1['str1'] ?? '';
            $str2 = $mat2['str1'] ?? '';
            $num1 = (isset($mat1['num1']) ? intval($mat1['num1']) : 0);
            $num2 = (isset($mat2['num1']) ? intval($mat2['num1']) : 0);
            return static::getSortedMotionsSortCmp($str1, $str2, $num1, $num2);
        }
    }

    /**
     * @param string[] $prefixes
     * @return string[]
     */
    public static function getSortedMotionsSortTest(array $prefixes): array
    {
        usort($prefixes, [static::class, 'getSortedMotionsSort']);
        return $prefixes;
    }

    public static function imotionIsVisibleOnHomePage(IMotion $imotion, array $invisibleStatuses): bool
    {
        if (in_array($imotion->status, $invisibleStatuses)) {
            return false;
        }

        // For resolutions, both the resolution and the original motion should be shown
        $replacedInvisible = $invisibleStatuses;
        $replacedInvisible[] = IMotion::STATUS_RESOLUTION_FINAL;
        $replacedInvisible[] = IMotion::STATUS_RESOLUTION_PRELIMINARY;

        if (is_a($imotion, Motion::class)) {
            foreach (MotionRepository::getReplacedByMotionsWithinConsultation($imotion) as $replacedByMotion) {
                if (!in_array($replacedByMotion->status, $replacedInvisible)) {
                    // The motion to be checked is replaced by another motion that is visible
                    return false;
                }
            }
        }
        return true;
    }

    public static function resolutionIsVisibleOnHomePage(IMotion $motion): bool
    {
        if (!is_a($motion, Motion::class)) {
            return false;
        }
        if (count(MotionRepository::getReplacedByMotionsWithinConsultation($motion)) > 0) {
            return false;
        }
        return $motion->isResolution();
    }

    /**
     * @param IMotion[] $motions
     * @return array{string: array<IMotion>}
     */
    public static function getSortedMotionsStd(Consultation $consultation, array $motions): array
    {
        /** @var array{string: array<IMotion>} $motionsSorted */
        $motionsSorted   = [];
        /** @var array{string: array<IMotion>} $motionsNoPrefix */
        $motionsNoPrefix = [];

        $invisible   = $consultation->getStatuses()->getInvisibleMotionStatuses();
        $invisible[] = IMotion::STATUS_MODIFIED;

        foreach ($motions as $motion) {
            if (!self::imotionIsVisibleOnHomePage($motion, $invisible)) {
                continue;
            }

            $typeName = '';

            if (!isset($motionsSorted[$typeName])) {
                $motionsSorted[$typeName]   = [];
                $motionsNoPrefix[$typeName] = [];
            }
            $key = $motion->titlePrefix;
            if (is_a($motion, Motion::class) && $motion->isResolution()) {
                $key .= '-resolution'; // For resolutions, both the resolution and the original motion should be shown
            }

            if ($key === '') {
                $motionsNoPrefix[$typeName][] = $motion;
            } else {
                $motionsSorted[$typeName][$key] = $motion;
            }
        }

        foreach (array_keys($motionsSorted) as $key) {
            uksort($motionsSorted[$key], [self::class, 'getSortedMotionsSort']);
        }
        foreach ($motionsNoPrefix as $key => $noPreMot) {
            $motionsSorted[$key] = array_merge($motionsSorted[$key], $noPreMot);
        }

        return $motionsSorted;
    }

    /**
     * @param IMotion[] $imotions
     * @return array|array[]
     */
    public static function getSortedIMotionsAgenda(Consultation $consultation, array $imotions): array
    {
        $motionsSorted       = [];
        $foundMotionIds      = [];
        $motionIdsToBeSorted = [];

        foreach ($imotions as $imotion) {
            $motionIdsToBeSorted[] = $imotion->id;
            // @TODO A differenciation between motions and amendments will be necessary
        }

        $statuses = $consultation->getStatuses()->getInvisibleMotionStatuses();
        $items = ConsultationAgendaItem::getSortedFromConsultation($consultation);
        foreach ($items as $agendaItem) {
            $agendaMotions = $agendaItem->getMyIMotions(IMotionStatusFilter::onlyUserVisible($consultation, true));
            foreach ($agendaMotions as $agendaMotion) {
                if (!in_array($agendaMotion->id, $motionIdsToBeSorted)) {
                    continue;
                }
                if (!self::imotionIsVisibleOnHomePage($agendaMotion, $statuses)) {
                    continue;
                }
                if (!isset($motionsSorted['agenda' . $agendaItem->id])) {
                    $motionsSorted['agenda' . $agendaItem->id] = [];
                }
                $key                                             = $agendaMotion->titlePrefix;
                $motionsSorted['agenda' . $agendaItem->id][$key] = $agendaMotion;
                $foundMotionIds[]                                = $agendaMotion->id;
            }
        }
        foreach ($imotions as $motion) {
            if (!in_array($motion->id, $foundMotionIds)) {
                if (!isset($motionsSorted['noAgenda'])) {
                    $motionsSorted['noAgenda'] = [];
                }
                $motionsSorted['noAgenda'][$motion->titlePrefix] = $motion;
            }
        }

        foreach (array_keys($motionsSorted) as $key) {
            uksort($motionsSorted[$key], [self::class, 'getSortedMotionsSort']);
        }

        return $motionsSorted;
    }

    /**
     * @param IMotion[] $imotions
     * @return array|array[]
     */
    public static function getSortedIMotions(Consultation $consultation, array $imotions): array
    {
        switch ($consultation->getSettings()->startLayoutType) {
            case \app\models\settings\Consultation::START_LAYOUT_AGENDA:
            case \app\models\settings\Consultation::START_LAYOUT_AGENDA_LONG:
                return static::getSortedIMotionsAgenda($consultation, $imotions);
            // @TODO Tags?
            default:
                return static::getSortedMotionsStd($consultation, $imotions);
        }
    }

    /**
     * @param IMotion[] $imotions
     * @return IMotion[]
     */
    public static function getSortedIMotionsFlat(Consultation $consultation, array $imotions): array
    {
        $motions2   = static::getSortedIMotions($consultation, $imotions);
        $motionsOut = [];
        foreach ($motions2 as $vals) {
            foreach ($vals as $mot) {
                $motionsOut[] = $mot;
            }
        }
        return $motionsOut;
    }

    /**
     * @param Amendment[] $amendments
     * @return Amendment[]
     * @throws \app\models\exceptions\Internal
     */
    public static function getSortedAmendments(Consultation $consultation, array $amendments): array
    {
        if ($consultation->amendmentNumbering === ByLine::getID()) {
            return Amendment::sortByLineNumbers($consultation, $amendments);
        } else {
            usort($amendments, function (Amendment $am1, Amendment $am2) {
                return static::getSortedMotionsSort($am1->titlePrefix ?? '', $am2->titlePrefix ?? '');
            });
            return $amendments;
        }
    }

    /**
     * @param Motion[] $allMotions
     * @return IMotion[][]
     */
    public static function getIMotionsAndResolutions(array $allMotions): array
    {
        $motions     = [];
        $resolutions = [];
        foreach ($allMotions as $mot) {
            if (!$mot->isReadable()) {
                continue;
            }
            if ($mot->getMyMotionType()->amendmentsOnly) {
                foreach ($mot->amendments as $amendment) {
                    $motions[] = $amendment;
                }
            } elseif ($mot->isResolution()) {
                if (count(MotionRepository::getReplacedByMotionsWithinConsultation($mot)) === 0) {
                    $resolutions[] = $mot;
                }
            } else {
                $motions[] = $mot;
            }
        }
        return [$motions, $resolutions];
    }
}
