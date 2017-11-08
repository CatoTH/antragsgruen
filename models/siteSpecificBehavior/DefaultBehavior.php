<?php
namespace app\models\siteSpecificBehavior;

use app\components\MotionSorter;
use app\models\db\IMotionSection;

class DefaultBehavior
{
    /**
     * @param string $prefix1
     * @param string $prefix2
     * @return int
     * @SuppressWarnings(PHPMD.CyclomaticComplexity,PHPMD.NPathComplexity)
     */
    public static function getSortedMotionsSort($prefix1, $prefix2)
    {
        return MotionSorter::getSortedMotionsSort($prefix1, $prefix2);
    }

    /**
     * @param IMotionSection $section
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function showSectionIntroductionInPdf($section)
    {
        return true;
    }

    /**
     * @param int[] $stati
     * @return int[]
     */
    public static function getProposedChangeStati($stati)
    {
        return $stati;
    }
}
