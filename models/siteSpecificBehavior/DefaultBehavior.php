<?php

namespace app\models\siteSpecificBehavior;

use app\components\MotionSorter;
use app\models\db\Consultation;
use app\models\db\IMotionSection;
use app\models\policies\IPolicy;

class DefaultBehavior
{
    /**
     * @return string|Permissions
     */
    public static function getPermissionsClass()
    {
        return Permissions::class;
    }

    /**
     * @return string[]|IPolicy[]
     */
    public static function getCustomPolicies()
    {
        return [];
    }

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

    /**
     * @return bool
     */
    public static function hasSiteHomePage()
    {
        return false;
    }

    /**
     * @return null|string
     */
    public static function getSiteHomePage()
    {
        return null;
    }

    /**
     * @param Consultation $consultation
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function getConsultationSettingsForm(Consultation $consultation)
    {
        return '';
    }

    /**
     * @param Consultation $consultation
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function saveConsultationSettings(Consultation $consultation)
    {
    }
}
