<?php
namespace app\components;

use app\models\amendmentNumbering\ByLine;
use app\models\db\Amendment;
use app\models\db\Consultation;
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
                return -1;
            }
            if ($num1 > $num2) {
                return 1;
            }
            return 0;
        } else {
            if ($str1 < $str2) {
                return -1;
            }
            if ($str1 > $str2) {
                return 1;
            }
            return 0;
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
        if ($prefix1 == "" && $prefix2 == "") {
            return 0;
        }
        if ($prefix1 == "") {
            return -1;
        }
        if ($prefix2 == "") {
            return 1;
        }

        $prefix1 = preg_replace("/neu$/siu", "neu1", $prefix1);
        $prefix2 = preg_replace("/neu$/siu", "neu1", $prefix2);

        $pat1 = "/^(?<str1>[^0-9]*)(?<num1>[0-9]*)/siu";
        $pat2 = "/^(?<str1>[^0-9]*)(?<num1>[0-9]+)(?<str2>[^0-9]+)(?<num2>[0-9]+)$/siu";

        if (preg_match($pat2, $prefix1, $mat1) && preg_match($pat2, $prefix2, $mat2)) {
            if ($mat1["str1"] == $mat2["str1"] && $mat1["num1"] == $mat2["num1"]) {
                return static::getSortedMotionsSortCmp($mat1["str2"], $mat2["str2"], $mat1["num2"], $mat2["num2"]);
            } else {
                return static::getSortedMotionsSortCmp($mat1["str1"], $mat2["str1"], $mat1["num1"], $mat2["num1"]);
            }
        } elseif (preg_match($pat2, $prefix1, $mat1) && preg_match($pat1, $prefix2, $mat2)) {
            if ($mat1["str1"] == $mat2["str1"] && $mat1["num1"] == $mat2["num1"]) {
                return 1;
            } else {
                return static::getSortedMotionsSortCmp($mat1["str1"], $mat2["str1"], $mat1["num1"], $mat2["num1"]);
            }
        } elseif (preg_match($pat1, $prefix1, $mat1) && preg_match($pat2, $prefix2, $mat2)) {
            if ($mat1["str1"] == $mat2["str1"] && $mat1["num1"] == $mat2["num1"]) {
                return -1;
            } else {
                return static::getSortedMotionsSortCmp($mat1["str1"], $mat2["str1"], $mat1["num1"], $mat2["num1"]);
            }
        } else {
            preg_match($pat1, $prefix1, $mat1);
            preg_match($pat1, $prefix2, $mat2);
            $str1 = (isset($mat1["str1"]) ? $mat1["str1"] : "");
            $str2 = (isset($mat2["str1"]) ? $mat2["str1"] : "");
            $num1 = (isset($mat1["num1"]) ? $mat1["num1"] : "");
            $num2 = (isset($mat2["num1"]) ? $mat2["num1"] : "");
            return static::getSortedMotionsSortCmp($str1, $str2, $num1, $num2);
        }
    }


    /**
     * @param Consultation $consultation
     * @param Motion[] $motions
     * @return array|array[]
     */
    public static function getSortedMotions(Consultation $consultation, $motions)
    {
        $motionsSorted = array();

        $inivisible   = $consultation->getInvisibleMotionStati();
        $inivisible[] = IMotion::STATUS_MODIFIED;

        foreach ($motions as $motion) {
            if (!in_array($motion->status, $inivisible)) {
                //$motion->tags // @TODO
                $typeName = "";

                if (!isset($motionsSorted[$typeName])) {
                    $motionsSorted[$typeName] = array();
                }
                $key = $motion->titlePrefix;

                $motionsSorted[$typeName][$key] = $motion;
            }
        }

        foreach (array_keys($motionsSorted) as $key) {
            uksort($motionsSorted[$key], array(static::class, "getSortedMotionsSort"));
        }

        return $motionsSorted;
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
