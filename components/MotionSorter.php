<?php

namespace app\components;

use app\models\amendmentNumbering\ByLine;
use app\models\db\{Amendment, Consultation, ConsultationAgendaItem, IMotion, Motion};

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
            if ($str1 < $str2) {
                $return = -1;
            } elseif ($str1 > $str2) {
                $return = 1;
            } else {
                $return = 0;
            }
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
            $str1 = (isset($mat1['str1']) ? $mat1['str1'] : '');
            $str2 = (isset($mat2['str1']) ? $mat2['str1'] : '');
            $num1 = (isset($mat1['num1']) ? intval($mat1['num1']) : 0);
            $num2 = (isset($mat2['num1']) ? intval($mat2['num1']) : 0);
            return static::getSortedMotionsSortCmp($str1, $str2, $num1, $num2);
        }
    }

    /**
     * @param string[] $prefixes
     * @return string[]
     */
    public static function getSortedMotionsSortTest($prefixes)
    {
        usort($prefixes, [static::class, 'getSortedMotionsSort']);
        return $prefixes;
    }


    /**
     * @param Consultation $consultation
     * @param IMotion[] $motions
     * @return IMotion[]
     */
    public static function getSortedMotionsStd(Consultation $consultation, $motions): array
    {
        $motionsSorted   = [];
        $motionsNoPrefix = [];

        $inivisible   = $consultation->getInvisibleMotionStatuses();
        $inivisible[] = IMotion::STATUS_MODIFIED;

        foreach ($motions as $motion) {
            if (!in_array($motion->status, $inivisible)) {
                $typeName = '';

                if (!isset($motionsSorted[$typeName])) {
                    $motionsSorted[$typeName]   = [];
                    $motionsNoPrefix[$typeName] = [];
                }
                $key = $motion->titlePrefix;

                if ($key === '') {
                    $motionsNoPrefix[$typeName][] = $motion;
                } else {
                    $motionsSorted[$typeName][$key] = $motion;
                }
            }
        }

        $siteBehavior = $consultation->site->getBehaviorClass();
        foreach (array_keys($motionsSorted) as $key) {
            uksort($motionsSorted[$key], [get_class($siteBehavior), 'getSortedMotionsSort']);
        }
        foreach ($motionsNoPrefix as $key => $noPreMot) {
            $motionsSorted[$key] = array_merge($motionsSorted[$key], $noPreMot);
        }

        return $motionsSorted;
    }

    /**
     * @param Consultation $consultation
     * @param Motion[] $motions
     * @return array|array[]
     */
    public static function getSortedMotionsAgenda(Consultation $consultation, $motions)
    {
        $motionsSorted       = [];
        $foundMotionIds      = [];
        $motionIdsToBeSorted = [];

        foreach ($motions as $motion) {
            $motionIdsToBeSorted[] = $motion->id;
        }

        $inivisible   = $consultation->getInvisibleMotionStatuses();
        $inivisible[] = IMotion::STATUS_MODIFIED;

        $items = ConsultationAgendaItem::getSortedFromConsultation($consultation);
        foreach ($items as $agendaItem) {
            $agendaMotions = $agendaItem->getVisibleMotions();
            foreach ($agendaMotions as $agendaMotion) {
                if (!in_array($agendaMotion->id, $motionIdsToBeSorted)) {
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
        foreach ($motions as $motion) {
            if (!in_array($motion->id, $foundMotionIds)) {
                if (!isset($motionsSorted['noAgenda'])) {
                    $motionsSorted['noAgenda'] = [];
                }
                $motionsSorted['noAgenda'][$motion->titlePrefix] = $motion;
            }
        }

        $siteBehavior = $consultation->site->getBehaviorClass();
        foreach (array_keys($motionsSorted) as $key) {
            uksort($motionsSorted[$key], [get_class($siteBehavior), 'getSortedMotionsSort']);
        }

        return $motionsSorted;
    }

    /**
     * @param Consultation $consultation
     * @param Motion[] $motions
     * @return array|array[]
     */
    public static function getSortedMotions(Consultation $consultation, $motions)
    {
        switch ($consultation->getSettings()->startLayoutType) {
            case \app\models\settings\Consultation::START_LAYOUT_AGENDA:
            case \app\models\settings\Consultation::START_LAYOUT_AGENDA_LONG:
                return static::getSortedMotionsAgenda($consultation, $motions);
            // @TODO Tags?
            default:
                return static::getSortedMotionsStd($consultation, $motions);
        }
    }

    /**
     * @param Consultation $consultation
     * @param Motion[] $motions
     * @return Motion[]
     */
    public static function getSortedMotionsFlat(Consultation $consultation, $motions)
    {
        $motions2   = static::getSortedMotions($consultation, $motions);
        $motionsOut = [];
        foreach ($motions2 as $vals) {
            foreach ($vals as $mot) {
                $motionsOut[] = $mot;
            }
        }
        return $motionsOut;
    }

    /**
     * @param Consultation $consultation
     * @param Amendment[] $amendments
     * @return Amendment[]
     * @throws \app\models\exceptions\Internal
     */
    public static function getSortedAmendments(Consultation $consultation, $amendments)
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
    public static function getMotionsAndResolutions($allMotions)
    {
        $motions     = [];
        $resolutions = [];
        foreach ($allMotions as $mot) {
            if ($mot->getMyMotionType()->amendmentsOnly) {
                foreach ($mot->amendments as $amendment) {
                    $motions[] = $amendment;
                }
            } elseif ($mot->isResolution()) {
                if (count($mot->getVisibleReplacedByMotions()) === 0) {
                    $resolutions[] = $mot;
                }
            } else {
                $motions[] = $mot;
            }
        }
        return [$motions, $resolutions];
    }
}
