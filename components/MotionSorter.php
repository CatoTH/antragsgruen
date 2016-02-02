<?php
namespace app\components;

use app\models\amendmentNumbering\ByLine;
use app\models\db\Amendment;
use app\models\db\Consultation;
use app\models\db\ConsultationAgendaItem;
use app\models\db\IMotion;
use app\models\db\Motion;

class MotionSorter
{
    /**
     * @param string $str1
     * @param string $str2
     * @param int $num1
     * @param int $num2
     * @return int
     */
    protected static function getSortedMotionsSortCmp($str1, $str2, $num1, $num2)
    {
        if ($str1 == $str2) {
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

    /**
     * @param string $str1
     * @param string $str2
     * @return string[]
     */
    public static function stripCommonBeginning($str1, $str2)
    {
        if ($str1 == '' || $str2 == '') {
            return [$str1, $str2];
        }
        if (is_numeric($str1[0]) && is_numeric($str2[0])) {
            if (IntVal($str1) == IntVal($str2) && $str1 != $str2) {
                $str1s = preg_replace('/^[0-9]+[\.\- ]+/', '', $str1);
                $str2s = preg_replace('/^[0-9]+[\.\- ]+/', '', $str2);
                if ($str1s == $str1 || $str2s == $str2) {
                    return [$str1, $str2];
                } else {
                    return static::stripCommonBeginning($str1s, $str2s);
                }
            } else {
                return [$str1, $str2];
            }
        } elseif (!is_numeric($str1[0]) && !is_numeric($str2[0]) && $str1[0] == $str2[0]) {
            return static::stripCommonBeginning(mb_substr($str1, 1), mb_substr($str2, 1));
        } else {
            return [$str1, $str2];
        }
    }

    /**
     * @param string $prefix1
     * @param string $prefix2
     * @return int
     * @SuppressWarnings(PHPMD.CyclomaticComplexity,PHPMD.NPathComplexity)
     */
    public static function getSortedMotionsSort($prefix1, $prefix2)
    {
        if ($prefix1 == '' && $prefix2 == '') {
            return 0;
        }
        if ($prefix1 == '') {
            return -1;
        }
        if ($prefix2 == '') {
            return 1;
        }

        $prefix1 = preg_replace('/neu$/siu', 'neu1', $prefix1);
        $prefix2 = preg_replace('/neu$/siu', 'neu1', $prefix2);
        list($prefix1, $prefix2) = static::stripCommonBeginning($prefix1, $prefix2);

        $pat1 = '/^(?<str1>[^0-9]*)(?<num1>[0-9]*)/siu';
        $pat2 = '/^(?<str1>[^0-9]*)(?<num1>[0-9]+)(?<str2>[^0-9]+)(?<num2>[0-9]+)$/siu';

        if (preg_match($pat2, $prefix1, $mat1) && preg_match($pat2, $prefix2, $mat2)) {
            if ($mat1['str1'] == $mat2['str1'] && $mat1['num1'] == $mat2['num1']) {
                return static::getSortedMotionsSortCmp($mat1['str2'], $mat2['str2'], $mat1['num2'], $mat2['num2']);
            } else {
                return static::getSortedMotionsSortCmp($mat1['str1'], $mat2['str1'], $mat1['num1'], $mat2['num1']);
            }
        } elseif (preg_match($pat2, $prefix1, $mat1) && preg_match($pat1, $prefix2, $mat2)) {
            if ($mat1['str1'] == $mat2['str1'] && $mat1['num1'] == $mat2['num1']) {
                return 1;
            } else {
                return static::getSortedMotionsSortCmp($mat1['str1'], $mat2['str1'], $mat1['num1'], $mat2['num1']);
            }
        } elseif (preg_match($pat1, $prefix1, $mat1) && preg_match($pat2, $prefix2, $mat2)) {
            if ($mat1['str1'] == $mat2['str1'] && $mat1['num1'] == $mat2['num1']) {
                return -1;
            } else {
                return static::getSortedMotionsSortCmp($mat1['str1'], $mat2['str1'], $mat1['num1'], $mat2['num1']);
            }
        } else {
            preg_match($pat1, $prefix1, $mat1);
            preg_match($pat1, $prefix2, $mat2);
            $str1 = (isset($mat1['str1']) ? $mat1['str1'] : '');
            $str2 = (isset($mat2['str1']) ? $mat2['str1'] : '');
            $num1 = (isset($mat1['num1']) ? $mat1['num1'] : '');
            $num2 = (isset($mat2['num1']) ? $mat2['num1'] : '');
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
     * @param Motion[] $motions
     * @return array|array[]
     */
    public static function getSortedMotionsStd(Consultation $consultation, $motions)
    {
        $motionsSorted   = [];
        $motionsNoPrefix = [];

        $inivisible   = $consultation->getInvisibleMotionStati();
        $inivisible[] = IMotion::STATUS_MODIFIED;

        foreach ($motions as $motion) {
            if (!in_array($motion->status, $inivisible)) {
                $typeName = '';

                if (!isset($motionsSorted[$typeName])) {
                    $motionsSorted[$typeName]   = [];
                    $motionsNoPrefix[$typeName] = [];
                }
                $key = $motion->titlePrefix;

                if ($key == '') {
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

        $inivisible   = $consultation->getInvisibleMotionStati();
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
                $key = $agendaMotion->titlePrefix;
                $motionsSorted['agenda' . $agendaItem->id][$key] = $agendaMotion;
                $foundMotionIds[]                 = $agendaMotion->id;
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
     */
    public static function getSortedAmendments(Consultation $consultation, $amendments)
    {
        if (!$consultation->getSettings()->lineNumberingGlobal) {
            if ($consultation->amendmentNumbering == ByLine::getID()) {
                $amendments = Amendment::sortVisibleByLineNumbers($consultation, $amendments);
            }
        }
        return $amendments;
    }
}
