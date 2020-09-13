<?php /** @noinspection PhpUnusedParameterInspection */

namespace app\models\siteSpecificBehavior;

use app\components\MotionSorter;
use app\models\amendmentNumbering\IAmendmentNumbering;
use app\models\db\Consultation;
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
     * @return string[]|IAmendmentNumbering[]
     */
    public static function getCustomAmendmentNumberings(): array
    {
        return [];
    }

    /**
     * @param string $prefix1
     * @param string $prefix2
     * @return int
     */
    public static function getSortedMotionsSort($prefix1, $prefix2)
    {
        return MotionSorter::getSortedMotionsSort($prefix1, $prefix2);
    }

    /**
     * @param int[] $statuses
     * @return int[]
     */
    public static function getProposedChangeStatuses($statuses)
    {
        return $statuses;
    }

    public static function hasSiteHomePage(): bool
    {
        return false;
    }

    public static function getSiteHomePage(): ?string
    {
        return null;
    }

    public static function getConsultationHomePage(Consultation $consultation): ?string
    {
        return null;
    }

    public static function preferConsultationSpecificHomeLink(): bool
    {
        return false;
    }

    public static function siteHomeIsAlwaysPublic(): bool
    {
        return false;
    }
}
